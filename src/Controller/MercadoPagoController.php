<?php

namespace App\Controller;

use App\Entity\BookingPartner;
use App\Entity\CredencialesMercadoPago;
use App\Entity\EstadoReserva;
use App\Entity\MercadoPagoPago;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\SolicitudReserva;
use App\Entity\Usuario;
use App\Services\LanguageService;
use App\Services\MercadoPagoOnboardingService;
use App\Services\mailerServer;
use Doctrine\ORM\EntityManagerInterface;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MercadoPagoController extends AbstractController
{
    private ?CredencialesMercadoPago $credencialesPlataforma = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MercadoPagoOnboardingService $mercadoPagoOnboarding
    ) {
        $this->credencialesPlataforma = $this->em->getRepository(Plataforma::class)->find(1)?->getCredencialesMercadoPago();
    }
    #[Route('/pay/mercadopago/booking/{id}', name: 'mercadopago_pay_booking')]
    public function index(Request $request, SolicitudReserva $solicitudReserva): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $booking = $solicitudReserva->getBooking();
        $precioBoking = $this->em->getRepository(Precio::class)->findOneBy([
            'moneda' => 2,
            'booking' => $booking?->getId(),
        ]);

        if (!$plataforma instanceof Plataforma || !$booking || !$precioBoking) {
            return $this->redirectToRoute('app_inicio');
        }

        $adicionales = json_decode($solicitudReserva->getInChargeOf() ?? '[]', true) ?: [];
        $cantidad = is_countable($adicionales) ? count($adicionales) + 1 : 1;

        $partner = $booking->getBookingPartner();
        $credencial = $this->resolveCredentialsForPartner($partner);

        if (!$credencial instanceof CredencialesMercadoPago || !$credencial->getAccessToken()) {
            $this->addFlash('error', 'No se pudo iniciar el flujo de pago porque no hay credenciales de Mercado Pago disponibles.');

            return $this->redirectToRoute('app_inicio');
        }

        try {
            if ($this->mercadoPagoOnboarding->ensureValidAccessToken($credencial)) {
                $this->em->persist($credencial);
                $this->em->flush();
            }
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'No se pudo validar el acceso a Mercado Pago: ' . $exception->getMessage());

            return $this->redirectToRoute('app_inicio');
        }

        MercadoPagoConfig::setAccessToken($credencial->getAccessToken());

        $total = (float) $precioBoking->getValor() * $cantidad;
        $comision = $partner?->getComisionPlataforma();
        if ($comision === null) {
            $comision = $plataforma->getComisionBookingPartner();
        }
        $comision = (float) ($comision ?? 0);
        $applicationFee = round($total * ($comision / 100), 2);
        if ($applicationFee > $total) {
            $applicationFee = $total;
        }

        $client = new PreferenceClient();
        $preference = $client->create([
            'items' => [
                [
                    'id' => $solicitudReserva->getId(),
                    'title' => $booking->getNombre(),
                    'picture_url' => $this->generateUrl('app_inicio', [], UrlGeneratorInterface::ABSOLUTE_URL) . '/img/booking/' . $booking->getImgPortada(),
                    'quantity' => $cantidad,
                    'currency_id' => 'ARG',
                    'unit_price' => (float) $precioBoking->getValor(),
                ],
            ],
            'payer' => [
                'name' => $solicitudReserva->getName(),
                'surname' => $solicitudReserva->getSurname(),
                'email' => $solicitudReserva->getEmail(),
            ],
            'back_urls' => [
                'success' => $this->generateUrl('mercadopago_pay_booking_return', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'pending' => $this->generateUrl('mercadopago_pay_booking_return', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'failure' => $this->generateUrl('mercadopago_pay_booking_return', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
            'notification_url' => $this->generateUrl('mercadopago_ipn', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'auto_return' => 'approved',
            'external_reference' => sprintf('booking-%d-%d', $booking->getId(), $solicitudReserva->getId()),
            'expires' => false,
            'binary_mode' => true,
            'application_fee' => $applicationFee,
            'metadata' => [
                'booking_id' => $booking->getId(),
                'partner_id' => $partner?->getId(),
                'solicitud_reserva_id' => $solicitudReserva->getId(),
            ],
        ]);
        return $this->render('mercado_pago/index.html.twig', [
            'controller_name' => 'MercadoPagoController',
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'id' => $preference->id,
            'plataforma' => $plataforma,
            'publicKey' => $this->credencialesPlataforma?->getPublicKey(),
            'usuario' => $this->getUser(),
        ]);
    }
    #[Route('/pay/mercadopago/booking-return', name: 'mercadopago_pay_booking_return')]
    public function mercadopago_pay_booking_return(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $solicitudReserva = null;
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $contenido = $request->query->all();
        $paymentId = $contenido['payment_id'] ?? null;

        if (!$paymentId) {
            return $this->redirectToRoute('app_inicio');
        }

        [$pagoMP, $credencial] = $this->fetchPaymentUsingAnyCredential($paymentId);

        if (!$pagoMP || !$credencial) {
            return $this->redirectToRoute('app_inicio');
        }

        $pagoDB = $this->loadPayment($pagoMP, $credencial, $contenido);
        if (!$pagoDB) {
            return $this->redirectToRoute('app_inicio');
        }

        $solicitudReserva = $pagoDB->getSolicitudReserva();
        $linkDetalles = $solicitudReserva->getLinkDetalles();
        return $this->render('pago/returnBooking.html.twig', [
            'controller_name' => 'MercadoPagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'pago'=>$pagoDB,
            'plataforma'=>$plataforma,
            'linkDetalles' => $linkDetalles,
            'usuario' => $this->getUser(),
        ]);
    }
    #[Route('/pay/mercadopago/ipn', name: 'mercadopago_ipn', methods: ['POST'])]
    public function mercadopago_ipn(Request $request,MailerInterface $mailer): Response
    {
        $pagoMP = null;
        $contenido = $request->query->all();
        // Agrega credenciales
        $credenciales = $this->em->getRepository(CredencialesMercadoPago::class)->findAll();
        $respuesta = '';
        $pagoDB = null;
        foreach ($credenciales as $credencial){
            if (!$credencial->getAccessToken()) {
                continue;
            }

            MercadoPagoConfig::setAccessToken($credencial->getAccessToken());
            $pago = new PaymentClient();
            try {
                $pagoMP = $pago->get($contenido['id']);
                $respuesta = $respuesta .'payment:'.$contenido['id'].' '. json_encode($pagoMP);
                if(isset($pagoMP) && !empty($pagoMP)){
                    $pagoDB = $this->loadPayment($pagoMP, $credencial, $contenido);
                    break;
                }
            }catch ( MPApiException $e ){
                $respuesta = $respuesta .'payment:'.$contenido['id'].' '. json_encode($e->getApiResponse()->getContent());
            }
        }
        if(isset($pagoDB) && !empty($pagoDB)){
            $reserva = $pagoDB->getSolicitudReserva();
                if(isset($reserva) && !empty($reserva)){ //Enviar mail de pago aprobado para una reserva...
                    if($reserva->getEstado()->getId() == 2) {
                        $cantidad = count($reserva->getInChargeOfArray()) + 1;
                        mailerServer::enviarPagoAprobadoReserva($this->em,$mailer,$reserva,$this->generateUrl('app_status_booking',['tokenId'=> $reserva->getLinkDetalles(),'id'=>$reserva->getId()],UrlGeneratorInterface::ABSOLUTE_URL));

                        $administradores = $this->em->getRepository(Usuario::class)->obtenerUsuariosPorRol('ROLE_ADMIN');
                        $booking = $reserva->getBooking();
                        $mensajeAdmin = sprintf(
                            '%s reservó (%d) "%s".',
                            $reserva->getName(),
                            $cantidad,
                            $booking->getNombre()
                        );

                        \App\Services\notificacion::enviarMasivo(
                            $administradores,
                            $mensajeAdmin,
                            'Nueva reserva',
                            $this->generateUrl(
                                'app_administrador_booking',
                                ['id' => $booking->getId()],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            )
                        );

                        $partnerUser = $booking?->getBookingPartner()?->getUsuario();
                        if ($partnerUser instanceof Usuario) {
                            $mensajePartner = sprintf(
                                '%s confirmó una reserva (%d pasajeros) para "%s".',
                                $reserva->getName(),
                                $cantidad,
                                $booking->getNombre()
                            );

                            \App\Services\notificacion::enviarMasivo(
                                [$partnerUser],
                                $mensajePartner,
                                'Nueva reserva en tu servicio',
                                $this->generateUrl(
                                    'app_booking_partner_service_reservations',
                                    ['id' => $booking->getId()],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                )
                            );
                        }
                    }
                }
        }
        $archivo = fopen('artiMP.dim', 'a+');
        fwrite($archivo, PHP_EOL . json_encode(['date'=>(new \DateTime())->format('d-m-Y H:i:s'),$contenido]) );
        fclose($archivo);
        return new JsonResponse(['status'=>'success'],200);
    }


    private function resolveCredentialsForPartner(?BookingPartner $partner): ?CredencialesMercadoPago
    {
        $partnerCredentials = $partner?->getMercadoPagoCuenta();

        if ($partnerCredentials instanceof CredencialesMercadoPago && $partnerCredentials->getAccessToken()) {
            return $partnerCredentials;
        }

        return $this->credencialesPlataforma;
    }

    /**
     * @return array{0: ?Payment, 1: ?CredencialesMercadoPago}
     */
    private function fetchPaymentUsingAnyCredential(string $paymentId): array
    {
        $credenciales = $this->em->getRepository(CredencialesMercadoPago::class)->findAll();

        foreach ($credenciales as $credencial) {
            if (!$credencial instanceof CredencialesMercadoPago || !$credencial->getAccessToken()) {
                continue;
            }

            MercadoPagoConfig::setAccessToken($credencial->getAccessToken());
            $pago = new PaymentClient();

            try {
                $payment = $pago->get($paymentId);
            } catch (MPApiException) {
                continue;
            }

            if ($payment && isset($payment->id)) {
                return [$payment, $credencial];
            }
        }

        return [null, null];
    }

    private function loadPayment(Payment $pagoMP, CredencialesMercadoPago $credencial, array $payload = []): ?MercadoPagoPago
    {
        if (!$pagoMP || !isset($pagoMP->id)) {
            return null;
        }

        $pagoDB = $this->em->getRepository(MercadoPagoPago::class)->findOneBy(['paymentId' => $pagoMP->id]) ?? new MercadoPagoPago();
        $pagoDB->setCredencialesMercadoPago($credencial);
        $pagoDB->setPaymentId((int) $pagoMP->id);

        if (isset($payload['preference_id'])) {
            $pagoDB->setPreferenceId($payload['preference_id']);
        }

        if (isset($pagoMP->card)) {
            $pagoDB->setCard(json_encode($pagoMP->card));
        }

        if (isset($pagoMP->collector_id)) {
            $pagoDB->setCollectorId((string) $pagoMP->collector_id);
        }

        if (isset($pagoMP->transaction_details->net_received_amount)) {
            $pagoDB->setNetReceivedAmount((float) $pagoMP->transaction_details->net_received_amount);
        }

        if (isset($pagoMP->payer)) {
            $pagoDB->setPayer(json_encode($pagoMP->payer));
        }

        if (isset($pagoMP->payment_method_id)) {
            $pagoDB->setPaymentMethodId($pagoMP->payment_method_id);
        }

        if (isset($pagoMP->payment_type_id)) {
            $pagoDB->setPaymentTypeId($pagoMP->payment_type_id);
        }

        if (isset($pagoMP->status)) {
            $pagoDB->setStatus($pagoMP->status);
        }

        if (isset($pagoMP->transaction_amount)) {
            $pagoDB->setTransactionAmount((float) $pagoMP->transaction_amount);
        }

        if (isset($pagoMP->transaction_amount_refunded)) {
            $pagoDB->setTransactionAmountRefunded((float) $pagoMP->transaction_amount_refunded);
        }

        $feeDetailsRaw = isset($pagoMP->fee_details) ? json_decode(json_encode($pagoMP->fee_details), true) : [];
        $applicationFee = 0.0;
        if (is_array($feeDetailsRaw) && !empty($feeDetailsRaw)) {
            foreach ($feeDetailsRaw as $detail) {
                if (($detail['type'] ?? '') === 'application_fee') {
                    $applicationFee += (float) ($detail['amount'] ?? 0);
                }
            }
            $pagoDB->setFeeDetails(json_encode($feeDetailsRaw));
            $pagoDB->setApplicationFee($applicationFee);
        } else {
            $pagoDB->setFeeDetails(json_encode([]));
            $pagoDB->setApplicationFee(null);
        }

        $solicitudReserva = null;
        if (!empty($pagoMP->external_reference)) {
            $referencia = explode('-', (string) $pagoMP->external_reference);
            if (count($referencia) === 3 && $referencia[0] === 'booking') {
                $solicitudReserva = $this->em->getRepository(SolicitudReserva::class)->findOneBy([
                    'Booking' => $referencia[1],
                    'id' => $referencia[2],
                ]);
            }
        }

        if (!$solicitudReserva instanceof SolicitudReserva) {
            return null;
        }

        $pagoDB->setSolicitudReserva($solicitudReserva);

        $precioBoking = $this->em->getRepository(Precio::class)->findOneBy([
            'moneda' => 2,
            'booking' => $solicitudReserva->getBooking()?->getId(),
        ]);

        if ($precioBoking) {
            $pagado = 0.0;
            foreach ($solicitudReserva->getPagosMercadoPago() as $pagoReserva) {
                if ($pagoReserva->getStatus() === 'approved') {
                    $pagado += (float) $pagoReserva->getTransactionAmount() - (float) $pagoReserva->getTransactionAmountRefunded();
                }
            }

            if ($pagado >= (float) $precioBoking->getValor()) {
                $solicitudReserva->setEstado($this->em->getRepository(EstadoReserva::class)->find(2));
            }

            $this->em->persist($solicitudReserva);
        }

        $this->em->persist($pagoDB);
        $this->em->flush();

        return $pagoDB;
    }
}
