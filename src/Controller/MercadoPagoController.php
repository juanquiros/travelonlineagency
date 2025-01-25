<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\EstadoReserva;
use App\Entity\MercadoPagoPago;
use App\Entity\PayPalPago;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\SolicitudReserva;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;
use MercadoPago\Resources\Preference;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $precioBoking=$this->em->getRepository(Precio::class)->findOneBy(['moneda'=>2,'booking'=>$solicitudReserva->getBooking()->getId()]);
        if(!isset($precioBoking) || empty($precioBoking)) return $this->redirectToRoute('app_inicio');
        $adicionales = json_decode($solicitudReserva->getInChargeOf());
        $cantidad = count($adicionales ) + 1;
        // Agrega credenciales
        MercadoPagoConfig::setAccessToken($this->credencialesPlataforma->getAccessToken());

        $client = new PreferenceClient();
        dump($this->generateUrl('app_inicio',[],UrlGeneratorInterface::ABSOLUTE_URL).'img/booking/'.$solicitudReserva->getBooking()->getImgPortada());
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
                "success"=> "https://www.shophardware.com.ar",//$this->generateUrl('mercadopago_pay_booking_return',[],UrlGeneratorInterface::ABSOLUTE_URL),
                "pending"=> "https://www.shophardware.com.ar",
                "failure"=> "https://www.shophardware.com.ar"
            ],
            "notification_url"=>"https://www.shophardware.com.ar",// $this->generateUrl('mercadopago_ipn',[],UrlGeneratorInterface::ABSOLUTE_URL),
            "auto_return"=> "approved",
            "external_reference"=> 'booking-'. $solicitudReserva->getBooking()->getId().'-'.$solicitudReserva->getId(),
            "expires"=> false
        ]);
        dump($preference);
        return $this->render('mercado_pago/index.html.twig', [
            'controller_name' => 'MercadoPagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'id'=>$preference->id,
            'publicKey'=>$this->credencialesPlataforma->getPublicKey()
        ]);
    }
    #[Route('/pay/mercadopago/booking-return', name: 'mercadopago_pay_booking_return')]
    public function mercadopago_pay_booking_return(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $solicitudReserva = null;
        $contenido = $request->query->all();
        // Agrega credenciales
        MercadoPagoConfig::setAccessToken($this->credencialesPlataforma->getAccessToken());


        $pago = new PaymentClient();


        $pagoMP = $pago->get($contenido['payment_id']);
        if(!isset($pagoMP) || empty($pagoMP) || !isset($pagoMP->id)) return $this->redirectToRoute('app_inicio');

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
        $linkDetalles =$this->generateUrl('app_status_booking',['tokenId'=>base64_encode($pagoDB->getSolicitudReserva()->getId() . ':'.$pagoDB->getSolicitudReserva()->getEmail()),'id'=>$pagoDB->getSolicitudReserva()->getId()]);

        return $this->render('pago/returnBooking.html.twig', [
            'controller_name' => 'MercadoPagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'pago'=>$pagoDB,
            'linkDetalles'=>$linkDetalles,
        ]);


        //?collection_id=null
        //&collection_status=null
        //&payment_id=null
        //&status=null
        //&external_reference=booking-37-37
        //&payment_type=null
        //&merchant_order_id=null
        //&preference_id=1484301827-55089c8a-4e2b-4e3d-af1e-72931e5ab259
        //&site_id=MLA
        //&processing_mode=aggregator
        //&merchant_account_id=null


        ///
        /// ?collection_id=null
        /// &collection_status=null
        /// &payment_id=null
        /// &status=null
        /// &external_reference=booking-37-37
        /// &payment_type=null
        /// &merchant_order_id=null
        /// &preference_id=53917061-09d4b94e-d025-445e-81c1-8c8e94c3bb3f
        /// &site_id=MLA
        /// &processing_mode=aggregator
        /// &merchant_account_id=null

        ///?collection_id=99879584271
        /// &collection_status=approved
        /// &payment_id=99879584271
        /// &status=approved
        /// &external_reference=booking-37-37
        /// &payment_type=credit_card
        /// &merchant_order_id=27631845978
        /// &preference_id=53917061-6ed88da1-28f0-45a8-b948-90781bc787ca
        /// &site_id=MLA
        /// &processing_mode=aggregator
        /// &merchant_account_id=null


        ///?collection_id=100252454828&collection_status=approved&payment_id=100252454828&status=approved&external_reference=booking-37-63&payment_type=credit_card&merchant_order_id=27632669030&preference_id=53917061-7edc31d9-ba42-496f-8f47-31ff8d326583&site_id=MLA&processing_mode=aggregator&merchant_account_id=null
        ///
        /// array:11 [▼
        //  "collection_id" => "100252454828"
        //  "collection_status" => "approved"
        //  "payment_id" => "100252454828"
        //  "status" => "approved"
        //  "external_reference" => "booking-37-63"
        //  "payment_type" => "credit_card"
        //  "merchant_order_id" => "27632669030"
        //  "preference_id" => "53917061-7edc31d9-ba42-496f-8f47-31ff8d326583"
        //  "site_id" => "MLA"
        //  "processing_mode" => "aggregator"
        //  "merchant_account_id" => "null"
        //]
    }
    #[Route('/pay/mercadopago/ipn', name: 'mercadopago_ipn')]
    public function mercadopago_ipn(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);

        $contenido = $request->getContent();
        $archivo = fopen('ipn.txt', 'r+');

        fwrite($archivo, $contenido . PHP_EOL );

        fclose($archivo);
        return new Response(json_encode(['status'=>'success']),200);
        //?topic=payment
        //&id=123456
    }
}
