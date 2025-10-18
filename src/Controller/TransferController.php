<?php

namespace App\Controller;

use App\Entity\Plataforma;
use App\Entity\TransferAssignment;
use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
use App\Entity\TransferFormField;
use App\Entity\TransferRequest;
use App\Entity\TransferRequestDestination;
use App\Entity\TransferRequestFieldValue;
use App\Services\LanguageService;
use App\Services\mailerServer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class TransferController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
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

        if ($request->isMethod('POST')) {
            $solicitud = $this->crearSolicitud($request, $campos);
            if ($solicitud instanceof TransferRequest) {
                $this->em->persist($solicitud);
                $this->em->flush();

                $trackingUrl = $this->generateUrl('app_transfer_tracking', [
                    'token' => $solicitud->getTokenSeguimiento(),
                ], Route::ABSOLUTE_URL);

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

        return $this->render('transfer/summary.html.twig', [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
            'solicitud' => $solicitud,
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
        $trackingUrl = $this->generateUrl('app_transfer_tracking', ['token' => $token], Route::ABSOLUTE_URL);

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

        return $this->render('transfer/tracking.html.twig', [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
            'solicitud' => $solicitud,
            'trackingUrl' => $trackingUrl,
            'asignacion' => $asignacionActiva,
        ]);
    }

    private function crearSolicitud(Request $request, array $campos): ?TransferRequest
    {
        $tipo = $request->request->get('tipo', 'combo');
        $combo = null;
        $destinosSeleccionados = [];
        $errores = [];

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

        $nombre = trim((string) $request->request->get('nombre'));
        $email = trim((string) $request->request->get('email'));
        $telefono = trim((string) $request->request->get('telefono'));
        if ($nombre === '' || $email === '') {
            $errores[] = 'Completá tu nombre y correo electrónico para avanzar.';
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

        $solicitud = new TransferRequest();
        $solicitud->setNombrePasajero($nombre);
        $solicitud->setEmailPasajero($email);
        $solicitud->setTelefonoPasajero($telefono !== '' ? $telefono : null);
        $solicitud->setArribo($arribo);
        $solicitud->setSalida($salida);
        $solicitud->setTokenSeguimiento(bin2hex(random_bytes(12)));
        $solicitud->setNotasCliente($request->request->get('notas'));
        $solicitud->setMoneda('ARS');
        if ($this->getUser() !== null) {
            $solicitud->setUsuario($this->getUser());
        }

        if ($combo instanceof TransferCombo) {
            $solicitud->setTipo('combo');
            $solicitud->setCombo($combo);
            $solicitud->setPrecioTotal(number_format((float) $combo->getPrecio(), 2, '.', ''));
            foreach ($combo->getDestinos() as $indice => $detalle) {
                $this->agregarDestinoSolicitud($solicitud, $detalle->getDestino(), $indice + 1);
            }
        } else {
            $solicitud->setTipo('custom');
            $total = 0.0;
            foreach ($destinosSeleccionados as $index => $destino) {
                $total += (float) $destino->getTarifaBase();
                $this->agregarDestinoSolicitud($solicitud, $destino, $index + 1);
            }
            $solicitud->setPrecioTotal(number_format($total, 2, '.', ''));
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
