<?php

namespace App\Controller;

use App\Entity\CredencialesMercadoPago;
use App\Entity\EstadoReserva;
use App\Entity\MercadoPagoPago;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\SolicitudReserva;
use App\Entity\Usuario;
use App\Services\LanguageService;
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
    private $em;
    private $credencialesPlataforma = null;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->credencialesPlataforma = $this->em->getRepository(Plataforma::class)->find(1)->getCredencialesMercadoPago();
    }
    #[Route('/pay/mercadopago/booking/{id}', name: 'mercadopago_pay_booking')]
    public function index(Request $request, SolicitudReserva $solicitudReserva): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $precioBoking=$this->em->getRepository(Precio::class)->findOneBy(['moneda'=>2,'booking'=>$solicitudReserva->getBooking()->getId()]);
        if(!isset($precioBoking) || empty($precioBoking)) return $this->redirectToRoute('app_inicio');
        $adicionales = json_decode($solicitudReserva->getInChargeOf());
        $cantidad = count($adicionales ) + 1;
        // Agrega credenciales
        MercadoPagoConfig::setAccessToken($this->credencialesPlataforma->getAccessToken());
        $client = new PreferenceClient();
        $preference = $client->create([
            "items"=> [
                [
                    "id"=> $solicitudReserva->getId(),
                    "title"=> $solicitudReserva->getBooking()->getNombre(),
                    "picture_url"=> $this->generateUrl('app_inicio',[],UrlGeneratorInterface::ABSOLUTE_URL).'/img/booking/'.$solicitudReserva->getBooking()->getImgPortada(),
                    "quantity"=> $cantidad,
                    "currency_id"=> "ARG",
                    "unit_price"=> $precioBoking->getValor()
                ]
            ],
            "payer"=> [
                "name"=> $solicitudReserva->getName(),
                "surname"=> $solicitudReserva->getSurname(),
                "email"=> $solicitudReserva->getEmail()
            ],
            "back_urls"=> [
                "success"=> $this->generateUrl('mercadopago_pay_booking_return',[],UrlGeneratorInterface::ABSOLUTE_URL),
                "pending"=> $this->generateUrl('mercadopago_pay_booking_return',[],UrlGeneratorInterface::ABSOLUTE_URL),
                "failure"=> $this->generateUrl('mercadopago_pay_booking_return',[],UrlGeneratorInterface::ABSOLUTE_URL)
            ],
            "notification_url"=>$this->generateUrl('mercadopago_ipn',[],UrlGeneratorInterface::ABSOLUTE_URL),
            "auto_return"=> "approved",
            "external_reference"=> 'booking-'. $solicitudReserva->getBooking()->getId().'-'.$solicitudReserva->getId(),
            "expires"=> false
        ]);
        return $this->render('mercado_pago/index.html.twig', [
            'controller_name' => 'MercadoPagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'id'=>$preference->id,
            'plataforma'=>$plataforma,
            'publicKey'=>$this->credencialesPlataforma->getPublicKey()
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
        // Agrega credenciales
        MercadoPagoConfig::setAccessToken($this->credencialesPlataforma->getAccessToken());
        $pago = new PaymentClient();
        $pagoMP = $pago->get($contenido['payment_id']);
        if(!isset($pagoMP) || empty($pagoMP) || !isset($pagoMP->id)) return $this->redirectToRoute('app_inicio');
        $pagoDB = $this->loadPayment($pagoMP);
        if(!isset($pagoDB) || empty($pagoDB)) return $this->redirectToRoute('app_inicio');
        $solicitudReserva = $pagoDB->getSolicitudReserva();
        $linkDetalles = $solicitudReserva->getLinkDetalles();
        return $this->render('pago/returnBooking.html.twig', [
            'controller_name' => 'MercadoPagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'pago'=>$pagoDB,
            'plataforma'=>$plataforma,
            'linkDetalles'=>$linkDetalles,
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
            MercadoPagoConfig::setAccessToken($credencial->getAccessToken());
            $pago = new PaymentClient();
            try {
                $pagoMP = $pago->get($contenido['payment_id']);
                $respuesta = $respuesta .'payment:'.$contenido['payment_id'].' '. json_encode($pagoMP);
                if(isset($pagoMP) && !empty($pagoMP)){
                    $pagoDB = $this->loadPayment($pagoMP);
                    break;
                }
            }catch ( MPApiException $e ){
                $respuesta = $respuesta .'payment:'.$contenido['payment_id'].' '. json_encode($e->getApiResponse()->getContent());
            }
        }
        if(isset($pagoDB) && !empty($pagoDB)){
            $reserva = $pagoDB->getSolicitudReserva();
                if(isset($reserva) && !empty($reserva)){ //Enviar mail de pago aprobado para una reserva...
                    if($reserva->getEstado()->getId() == 2) {
                        $cantidad = count($reserva->getInChargeOfArray()) + 1;
                        mailerServer::enviarPagoAprobadoReserva($this->em,$mailer,$reserva,$this->generateUrl('app_status_booking',['tokenId'=> $reserva->getLinkDetalles(),'id'=>$reserva->getId()]));
                        $administradores = $this->em->getRepository(Usuario::class)->obtenerUsuariosPorRol('ROLE_ADMIN');
                        \App\Services\notificacion::enviarMasivo($administradores, $reserva->getName() . ' reservó (' . $cantidad .') "'. $reserva->getBooking()->getNombre(), 'Nueva reserva', $this->generateUrl('app_administrador_booking', ['id' => $reserva->getBooking()->getId()]));
                    }
                }
        }
        $archivo = fopen('artiMP.dim', 'a+');
        fwrite($archivo, PHP_EOL . json_encode(['date'=>(new \DateTime())->format('d-m-Y H:i:s'),$contenido]) );
        fclose($archivo);
        return new JsonResponse(['status'=>'success'],200);
    }


    private function loadPayment(Payment $pagoMP)
    {
        $solicitudReserva=null;
        $pagoDB = null;
        if(isset($pagoMP) && !empty($pagoMP)){
            $pagoDB = $this->em->getRepository(MercadoPagoPago::class)->findOneBy(['paymentId'=>$pagoMP->id]);
            if(!isset($pagoDB) || empty($pagoDB)) $pagoDB = new MercadoPagoPago();
            $pagoDB->setCredencialesMercadoPago($this->credencialesPlataforma);
            $pagoDB->setPaymentId($pagoMP->id);
            if(isset($pagoMP->card))$pagoDB->setCard(json_encode($pagoMP->card));
            if(isset($pagoMP->collector_id))$pagoDB->setCollectorId($pagoMP->collector_id);
            if(isset($pagoMP->fee_details))$pagoDB->setFeeDetails(json_encode($pagoMP->fee_details));
            if(isset($pagoMP->transaction_details) && isset($pagoMP->transaction_details->net_received_amount))$pagoDB->setNetReceivedAmount(floatval($pagoMP->transaction_details->net_received_amount));
            if(isset($pagoMP->payer))$pagoDB->setPayer(json_encode($pagoMP->payer));
            if(isset($pagoMP->payment_method_id))$pagoDB->setPaymentMethodId($pagoMP->payment_method_id);
            if(isset($pagoMP->payment_type_id))$pagoDB->setPaymentTypeId($pagoMP->payment_type_id);
            if(isset($contenido['preference_id']))$pagoDB->setPreferenceId($contenido['preference_id']);
            if(isset($contenido['preference_id']))$pagoDB->setPreferenceId($contenido['preference_id']);
            if(isset($pagoMP->status))$pagoDB->setStatus($pagoMP->status);
            if(isset($pagoMP->transaction_amount))$pagoDB->setTransactionAmount(floatval($pagoMP->transaction_amount));
            if(isset($pagoMP->transaction_amount_refunded))$pagoDB->setTransactionAmountRefunded(floatval($pagoMP->transaction_amount_refunded));
            $referencia = preg_split('/-/',$pagoMP->external_reference);
            if(isset($referencia) && !empty($referencia) && count($referencia) == 3 ){
                switch ($referencia[0]){
                    case 'booking':
                        $solicitudReserva = $this->em->getRepository(SolicitudReserva::class)->findOneBy(['Booking'=>$referencia[1],'id'=>$referencia[2]]);
                        if(!isset($solicitudReserva) || empty($solicitudReserva)) return $this->redirectToRoute('app_inicio');
                        $pagoDB->setSolicitudReserva($solicitudReserva);
                        $this->em->persist($pagoDB);
                        $this->em->flush();
                        break;
                }
            }
            if(isset($solicitudReserva) && !empty($solicitudReserva)){
                $precioBoking=$this->em->getRepository(Precio::class)->findOneBy(['moneda'=>2,'booking'=>$solicitudReserva->getBooking()->getId()]);
                if(!isset($precioBoking) || empty($precioBoking)) return $this->redirectToRoute('app_inicio');
                $aux= 0;
                foreach ($solicitudReserva->getPagosMercadoPago() as $pagoReserva){
                    if($pagoReserva->getStatus() === 'approved') $aux += $pagoReserva->getTransactionAmount() - $pagoReserva->getTransactionAmountRefunded();
                }
                if($aux >= $precioBoking->getValor())$solicitudReserva->setEstado($this->em->getRepository(EstadoReserva::class)->find(2));
                $this->em->persist($solicitudReserva);
            }
            $this->em->persist($pagoDB);
            $this->em->flush();
        }
        return $pagoDB;
    }
}
