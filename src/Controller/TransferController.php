<?php

namespace App\Controller;

use App\Entity\Plataforma;
use App\Entity\Moneda;
use App\Entity\TransferAssignment;
use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
use App\Entity\TransferFormField;
use App\Entity\TransferRequest;
use App\Entity\TransferRequestDestination;
use App\Entity\TransferRequestFieldValue;
use App\Entity\CashPayment;
use App\Entity\VehicleType;
use App\Form\TransferRatingType;
use App\Services\LanguageService;
use App\Services\PaymentOptionsResolver;
use App\Services\mailerServer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TransferController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly PaymentOptionsResolver $paymentOptions
    ) {
    }

    #[Route('/traslados', name: 'app_transfers', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $combos = $this->em->getRepository(TransferCombo::class)->findBy(['activo' => true], ['nombre' => 'ASC']);
        $destinos = $this->em->getRepository(TransferDestination::class)->findBy(['activo' => true], ['nombre' => 'ASC']);
        $campos = $this->em->getRepository(TransferFormField::class)->findForForm();
        $customEnabled = (bool) $plataforma->isTrasladosODLibres();
        $vehicleTypes = $this->resolveVehicleTypes();

        if ($request->isMethod('POST')) {
            $solicitud = $this->crearSolicitud($request, $campos, $customEnabled, $plataforma);
            if ($solicitud instanceof TransferRequest) {
                $this->em->persist($solicitud);
                $this->em->flush();

                $trackingUrl = $this->generateUrl('app_transfer_tracking', [
                    'token' => $solicitud->getTokenSeguimiento(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                mailerServer::enviarTrasladoSolicitud($this->em, $this->mailer, $solicitud, $trackingUrl);

                $this->addFlash('success', 'Tu solicitud de traslado fue registrada. Revisá tu correo para continuar con el pago.');

                return $this->redirectToRoute('app_transfer_summary', ['token' => $solicitud->getTokenSeguimiento()]);
            }
        }

        return $this->render('transfer/index.html.twig', [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
            'combos' => $combos,
            'destinos' => $destinos,
            'campos' => $campos,
            'customEnabled' => $customEnabled,
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    #[Route('/traslados/combos/{id}', name: 'app_transfer_combo_show', methods: ['GET'])]
    public function showCombo(TransferCombo $combo, Request $request): Response
    {
        if (!$combo->isActivo()) {
            throw $this->createNotFoundException();
        }

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $usuario = $this->getUser();

        $mapDefaults = [
            'lat' => -25.5972,
            'lng' => -54.5781,
        ];

        $mapDestinos = [];
        $destinosActivos = [];
        foreach ($combo->getDestinos() as $detalle) {
            $destino = $detalle->getDestino();
            if (!$destino instanceof TransferDestination || !$destino->isActivo()) {
                continue;
            }

            $destinosActivos[] = $destino;
            $mapDestinos[] = [
                'id' => $destino->getId(),
                'nombre' => $destino->getNombre(),
                'lat' => $destino->getCoordenadasLat(),
                'lng' => $destino->getCoordenadasLng(),
                'descripcionCorta' => $destino->getDescripcionCorta(),
                'categoria' => $destino->getCategoria() ? [
                    'id' => $destino->getCategoria()->getId(),
                    'nombre' => $destino->getCategoria()->getNombre(),
                    'icono' => $destino->getCategoria()->getIcono(),
                    'color' => $destino->getCategoria()->getColor(),
                ] : null,
            ];
        }

        $otrosCombos = array_filter(
            $this->em->getRepository(TransferCombo::class)->findBy(['activo' => true], ['nombre' => 'ASC']),
            static fn (TransferCombo $item) => $item->getId() !== $combo->getId()
        );

        $shareUrl = $this->generateUrl('app_transfer_combo_show', ['id' => $combo->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->render('frontend/combo_show.html.twig', [
            'combo' => $combo,
            'destinos' => $destinosActivos,
            'otrosCombos' => array_values($otrosCombos),
            'mapDestinos' => $mapDestinos,
            'mapDefaults' => $mapDefaults,
            'shareUrl' => $shareUrl,
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $usuario,
        ]);
    }

    #[Route('/traslados/resumen/{token}', name: 'app_transfer_summary')]
    public function summary(string $token, Request $request): Response
    {
        $solicitud = $this->em->getRepository(TransferRequest::class)->findOneBy(['tokenSeguimiento' => $token]);

        if (!$solicitud instanceof TransferRequest) {
            throw $this->createNotFoundException();
        }

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        $opciones = array_map(function (array $opcion) {
            if (($opcion['available'] ?? true) && isset($opcion['route'])) {
                $opcion['url'] = $this->generateUrl($opcion['route'], $opcion['params'] ?? []);
            }

            return $opcion;
        }, $this->paymentOptions->getTransferOptions($solicitud, $plataforma));
        $cashPayment = $this->em->getRepository(CashPayment::class)->findOneBy(
            ['transferRequest' => $solicitud],
            ['createdAt' => 'DESC']
        );

        $asignacionActiva = null;
        foreach ($solicitud->getAsignaciones() as $asignacion) {
            if (!in_array($asignacion->getEstado(), [
                TransferAssignment::ESTADO_CANCELADO,
                TransferAssignment::ESTADO_COMPLETADO,
            ], true)) {
                $asignacionActiva = $asignacion;
                break;
            }
        }

        return $this->render('transfer/summary.html.twig', [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
            'solicitud' => $solicitud,
            'opcionesPago' => $opciones,
            'pagoEfectivo' => $cashPayment,
            'asignacion' => $asignacionActiva,
        ]);
    }

    #[Route('/traslados/seguimiento/{token}', name: 'app_transfer_tracking')]
    public function tracking(string $token, Request $request): Response
    {
        $solicitud = $this->em->getRepository(TransferRequest::class)->findOneBy(['tokenSeguimiento' => $token]);

        if (!$solicitud instanceof TransferRequest) {
            throw $this->createNotFoundException();
        }

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $trackingUrl = $this->generateUrl('app_transfer_tracking', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $asignacionActiva = null;
        foreach ($solicitud->getAsignaciones() as $asignacion) {
            if (!in_array($asignacion->getEstado(), [
                TransferAssignment::ESTADO_CANCELADO,
                TransferAssignment::ESTADO_COMPLETADO,
            ], true)) {
                $asignacionActiva = $asignacion;
                break;
            }
        }

        $opciones = array_map(function (array $opcion) {
            if (($opcion['available'] ?? true) && isset($opcion['route'])) {
                $opcion['url'] = $this->generateUrl($opcion['route'], $opcion['params'] ?? []);
            }

            return $opcion;
        }, $this->paymentOptions->getTransferOptions($solicitud, $plataforma));
        $cashPayment = $this->em->getRepository(CashPayment::class)->findOneBy(
            ['transferRequest' => $solicitud],
            ['createdAt' => 'DESC']
        );

        $ratingForm = null;
        if ($solicitud->getEstado() === TransferRequest::ESTADO_COMPLETADO) {
            $ratingForm = $this->createForm(TransferRatingType::class, [
                'rating' => $solicitud->getCalificacion(),
                'comment' => $solicitud->getTestimonioComentario(),
            ]);
            $ratingForm->handleRequest($request);

            if ($ratingForm->isSubmitted() && $ratingForm->isValid()) {
                $data = $ratingForm->getData();
                $solicitud->setCalificacion((int) $data['rating']);
                $solicitud->setTestimonioComentario(trim((string) $data['comment']));
                $solicitud->setTestimonioCreadoEn(new \DateTimeImmutable());
                $this->em->flush();

                $this->addFlash('success', '¡Gracias por calificar tu traslado!');

                return $this->redirectToRoute('app_transfer_tracking', ['token' => $token]);
            }
        }

        return $this->render('transfer/tracking.html.twig', [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
            'solicitud' => $solicitud,
            'trackingUrl' => $trackingUrl,
            'asignacion' => $asignacionActiva,
            'opcionesPago' => $opciones,
            'pagoEfectivo' => $cashPayment,
            'ratingForm' => $ratingForm ? $ratingForm->createView() : null,
        ]);
    }

    private function crearSolicitud(Request $request, array $campos, bool $customEnabled, Plataforma $plataforma): ?TransferRequest
    {
        $tipo = $request->request->get('tipo', 'combo');
        $combo = null;
        $destinosSeleccionados = [];
        $errores = [];

        if ($tipo === 'custom' && !$customEnabled) {
            $this->addFlash('error', 'Los traslados personalizados no están disponibles en este momento.');

            return null;
        }

        if ($tipo === 'combo') {
            $comboId = (int) $request->request->get('combo_id');
            $combo = $this->em->getRepository(TransferCombo::class)->findOneBy(['id' => $comboId, 'activo' => true]);
            if (!$combo instanceof TransferCombo) {
                $errores[] = 'Seleccioná un combo válido para continuar.';
            }
        } else {
            $seleccion = $request->request->all('destinos');
            if (!is_array($seleccion) || count($seleccion) === 0) {
                $errores[] = 'Seleccioná al menos un destino para tu traslado personalizado.';
            } else {
                $destinosSeleccionados = $this->em->getRepository(TransferDestination::class)->findBy([
                    'id' => $seleccion,
                    'activo' => true,
                ]);
                if (count($destinosSeleccionados) === 0) {
                    $errores[] = 'Los destinos seleccionados no están disponibles.';
                }
            }
        }

        $defaultCurrencyIso = 'ARS';
        $defaultCurrency = $plataforma->getMonedaDef();
        if ($defaultCurrency instanceof Moneda) {
            $defaultCurrencyIso = $defaultCurrency->getCodigoIso() ?? $defaultCurrency->getSimbolo() ?? $defaultCurrencyIso;
        }

        $totalesPorMoneda = [];
        if ($combo instanceof TransferCombo) {
            $totalesPorMoneda = $combo->getPreciosDisponibles();
            if (empty($totalesPorMoneda)) {
                $errores[] = 'El combo seleccionado no tiene tarifas configuradas en ninguna moneda.';
            }
        } elseif (!empty($destinosSeleccionados)) {
            $totalesPorMoneda = $this->calcularTotalesDestinos($destinosSeleccionados, $errores);
        }

        $nombre = trim((string) $request->request->get('nombre'));
        $email = trim((string) $request->request->get('email'));
        $telefono = trim((string) $request->request->get('telefono'));
        $cantidadPasajerosRaw = $request->request->get('cantidad_pax');
        $cantidadPasajeros = null;
        if ($cantidadPasajerosRaw !== null && $cantidadPasajerosRaw !== '') {
            $cantidadPasajeros = (int) $cantidadPasajerosRaw;
        }
        $numeroVuelo = trim((string) $request->request->get('numero_vuelo'));
        $vehicleTypeId = (int) $request->request->get('tipo_vehiculo');
        $vehicleType = null;
        if ($nombre === '' || $email === '') {
            $errores[] = 'Completá tu nombre y correo electrónico para avanzar.';
        }

        if ($telefono === '') {
            $errores[] = 'Ingresá un teléfono de contacto para el pasajero.';
        }

        if ($cantidadPasajeros === null || $cantidadPasajeros <= 0) {
            $errores[] = 'Indicá la cantidad de pasajeros que viajarán en el traslado.';
        }

        if ($vehicleTypeId > 0) {
            $vehicleType = $this->em->getRepository(VehicleType::class)->find($vehicleTypeId);
        }

        if (!$vehicleType instanceof VehicleType || !$vehicleType->isActivo()) {
            $errores[] = 'Seleccioná un tipo de vehículo válido.';
        }

        $arribo = $this->parseDateTime($request->request->get('arribo'));
        $salida = $this->parseDateTime($request->request->get('salida'));

        $datosExtra = [];
        foreach ($campos as $campo) {
            $clave = 'campo_' . $campo->getClave();
            $valor = $request->request->get($clave);
            if ($campo->isRequerido() && ($valor === null || $valor === '')) {
                $errores[] = sprintf('El campo "%s" es obligatorio.', $campo->getEtiqueta());
            }
            if ($valor !== null && $valor !== '') {
                $datosExtra[$campo->getClave()] = $valor;
            }
        }

        if (!empty($errores)) {
            foreach ($errores as $error) {
                $this->addFlash('error', $error);
            }

            return null;
        }

        if (empty($totalesPorMoneda)) {
            $this->addFlash('error', 'No encontramos una tarifa disponible para tu selección. Consultá con el equipo de la plataforma.');

            return null;
        }

        $currencyIso = strtoupper($defaultCurrencyIso);
        if (!array_key_exists($currencyIso, $totalesPorMoneda)) {
            $currencyIso = array_key_first($totalesPorMoneda);
        }

        $solicitud = new TransferRequest();
        $solicitud->setNombrePasajero($nombre);
        $solicitud->setEmailPasajero($email);
        $solicitud->setTelefonoPasajero($telefono !== '' ? $telefono : null);
        $solicitud->setCantidadPasajeros($cantidadPasajeros);
        $solicitud->setVueloPasajero($numeroVuelo !== '' ? $numeroVuelo : null);
        if ($vehicleType instanceof VehicleType) {
            $solicitud->setVehicleType($vehicleType);
        }
        $solicitud->setArribo($arribo);
        $solicitud->setSalida($salida);
        $solicitud->setTokenSeguimiento(bin2hex(random_bytes(12)));
        if (!$solicitud->getCodigoServicio()) {
            $solicitud->setCodigoServicio($this->generarCodigoServicio());
        }
        $solicitud->setNotasCliente($request->request->get('notas'));
        $solicitud->setMoneda($currencyIso);
        if ($this->getUser() !== null) {
            $solicitud->setUsuario($this->getUser());
        }
        $solicitud->setTotalesPorMoneda($totalesPorMoneda);
        $montoSeleccionado = $totalesPorMoneda[$currencyIso] ?? reset($totalesPorMoneda);
        $solicitud->setPrecioTotal(number_format((float) $montoSeleccionado, 2, '.', ''));
        $solicitud->setMoneda($currencyIso);

        if ($combo instanceof TransferCombo) {
            $solicitud->setTipo('combo');
            $solicitud->setCombo($combo);
            foreach ($combo->getDestinos() as $indice => $detalle) {
                $this->agregarDestinoSolicitud($solicitud, $detalle->getDestino(), $indice + 1);
            }
        } else {
            $solicitud->setTipo('custom');
            foreach ($destinosSeleccionados as $index => $destino) {
                $this->agregarDestinoSolicitud($solicitud, $destino, $index + 1);
            }
        }

        if (!empty($datosExtra)) {
            $solicitud->setDatosExtra($datosExtra);
        }

        foreach ($campos as $campo) {
            $clave = 'campo_' . $campo->getClave();
            $valor = $request->request->get($clave);

            if ($valor === null || $valor === '') {
                continue;
            }

            $valorEntidad = new TransferRequestFieldValue();
            $valorEntidad->setSolicitud($solicitud);
            $valorEntidad->setCampo($campo);
            $valorEntidad->setValor((string) $valor);
            $solicitud->addValor($valorEntidad);
            $this->em->persist($valorEntidad);
        }

        return $solicitud;
    }

    /**
     * @param TransferDestination[] $destinos
     * @param string[] $errores
     * @return array<string,float>
     */
    private function calcularTotalesDestinos(array $destinos, array &$errores): array
    {
        if (empty($destinos)) {
            return [];
        }

        $maps = [];
        foreach ($destinos as $index => $destino) {
            if (!$destino instanceof TransferDestination) {
                continue;
            }
            $disponibles = $destino->getPreciosDisponibles();
            if ($disponibles === []) {
                $errores[] = sprintf('El destino "%s" no tiene tarifas configuradas. Actualizá el catálogo antes de ofrecerlo.', $destino->getNombre());

                return [];
            }
            $maps[$index] = $disponibles;
        }

        if ($maps === []) {
            return [];
        }

        $commonIsos = array_keys(reset($maps));
        foreach ($maps as $map) {
            $commonIsos = array_values(array_intersect($commonIsos, array_keys($map)));
        }

        if ($commonIsos === []) {
            $errores[] = 'Los destinos seleccionados no comparten una moneda disponible. Configurá tarifas en una moneda común para continuar.';

            return [];
        }

        $totales = [];
        foreach ($commonIsos as $iso) {
            $total = 0.0;
            foreach ($maps as $map) {
                $total += (float) ($map[$iso] ?? 0.0);
            }
            $totales[$iso] = $total;
        }

        return $totales;
    }

    /**
     * @return VehicleType[]
     */
    private function resolveVehicleTypes(): array
    {
        return $this->em->getRepository(VehicleType::class)->findActiveOrdered();
    }

    private function generarCodigoServicio(): string
    {
        $repository = $this->em->getRepository(TransferRequest::class);

        do {
            $codigo = sprintf('TRF-%s', strtoupper(bin2hex(random_bytes(3))));
        } while ($repository->findOneBy(['codigoServicio' => $codigo]) instanceof TransferRequest);

        return $codigo;
    }

    private function agregarDestinoSolicitud(TransferRequest $solicitud, ?TransferDestination $destino, int $posicion): void
    {
        if (!$destino instanceof TransferDestination) {
            return;
        }

        $detalle = new TransferRequestDestination();
        $detalle->setSolicitud($solicitud);
        $detalle->setDestino($destino);
        $detalle->setPosicion($posicion);
        $solicitud->addDestino($detalle);
        $this->em->persist($detalle);
    }

    private function parseDateTime(?string $value): ?\DateTimeInterface
    {
        if (!$value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
        if ($date instanceof \DateTimeImmutable) {
            return \DateTime::createFromImmutable($date);
        }

        return null;
    }
}
