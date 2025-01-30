<?php

namespace App\Controller;

use App\Entity\DetallePagoPayPal;
use App\Entity\EstadoReserva;
use App\Entity\PayPalPago;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\SolicitudReserva;
use App\Entity\Usuario;
use App\Service\notificacion;
use App\Services\LanguageService;
use App\Services\mailerServer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PayPalController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/pay/paypal/booking/{id}', name: 'paypal_pay_booking')]
    public function index(SolicitudReserva $reserva,Request $request): Response
    {
        if($reserva->getEstado()->getId() != 1) return $this->redirectToRoute('app_inicio'); //redireccionar a pagina de estado de reserva ->>>>> gotostatus
        $precioBoking=$this->em->getRepository(Precio::class)->findOneBy(['moneda'=>1,'booking'=>$reserva->getBooking()->getId()]);
        $pago = $this->em->getRepository(PayPalPago::class)->findOneBy(['estado'=>'PAYER_ACTION_REQUIRED','solicitudReserva'=>$reserva->getId()]);
        if(!isset($precioBoking) || empty($precioBoking)) return $this->redirectToRoute('app_inicio');
        $adicionales = json_decode($reserva->getInChargeOf());
        $cantidad = count($adicionales ) + 1;
        $total = $precioBoking->getValor() * $cantidad;
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        if(!isset($pago) || empty($pago)){
            $credenciales = $plataforma->getCredencialesPayPal();
            $pago = new PayPalPago();
            $pago->setCredencialesPayPal($credenciales);
            $pago->setSolicitudReserva($reserva);
        }else{
            $credenciales = $pago->getCredencialesPayPal();
        }
        $token = null;
        $accesToken = $credenciales->getAccessToken();
        if(isset($accesToken) && !empty($accesToken)) {
            $fechavence = \DateTime::createFromImmutable($credenciales->getUpdatedAt());
            $expirein = $credenciales->getExpiresIn() . ' seconds';
            date_add($fechavence, date_interval_create_from_date_string($expirein));
            if(new \DateTime() >= $fechavence){
                $token = $this->getTokenPaypal($credenciales->getClientId(),$credenciales->getClientSecret())['token'];
            }
        }else{
            $token = $this->getTokenPaypal($credenciales->getClientId(),$credenciales->getClientSecret())['token'];
        }
        if(isset($token) && !empty($token) && isset($token['access_token']) && !empty($token['access_token'])){
            $credenciales->setAccessToken($token['access_token']);
            $credenciales->setTokenType($token['token_type']);
            $credenciales->setAppId($token['app_id']);
            $credenciales->setExpiresIn(intval($token['expires_in']));
            $credenciales->setNonce($token['nonce']);
            $credenciales->setScope($token['scope']);
            $this->em->persist($credenciales);
            $this->em->flush();
        }

        $url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders';
        $params = [
            'intent' => "CAPTURE",
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'user_action' => 'PAY_NOW',
                        'return_url' => $this->generateUrl('paypal_return_booking', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        'cancel_url' => $this->generateUrl('paypal_return_booking', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                ]
            ],
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => $total,
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'USD',
                            'value' => $total
                        ]
                    ]
                ],
                'items' => [[
                    'name' => 'Booking (' . $cantidad . ')',
                    'description' => $reserva->getBooking()->getNombre(),
                    'unit_amount' => [
                        'currency_code' => 'USD',
                        'value' => $total
                    ],
                    'quantity' => '1',
                    'sku' => 'BOOKING00' . $reserva->getId()
                ]]
            ]]
        ];
        $ch = curl_init();
        $autorization = 'Bearer ' . $credenciales->getAccessToken();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'content-type: application/json',
            'Authorization:' . $autorization
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            json_encode($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $response = curl_exec($ch);
        $link = '#';
        if (curl_errno($ch)) {

        } else {
            $decodedResponse = json_decode($response, true);
            dump($decodedResponse);
            if ($decodedResponse !== null && isset($decodedResponse['links'])) {
                if(isset($decodedResponse['status']) && $decodedResponse['status']=== 'PAYER_ACTION_REQUIRED')$pago->setOrdersId($decodedResponse['id']);
                foreach ($decodedResponse['links'] as $linkresponse) {
                    if (isset($linkresponse['rel']) && $linkresponse['rel'] === "payer-action"   ){
                        $link = $linkresponse['href'];
                        $pago->setOrdersId($decodedResponse['id']);
                        $pago->setEstado($decodedResponse['status']);
                        $pago->setTotal($total);

                    }
                }
            }
        }
        curl_close($ch);
        $this->em->persist($pago);
        $this->em->flush();
        return $this->render('pay_pal/index.html.twig', [
            'controller_name' => 'PayPalController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'booking'=>$reserva,
            'cantidad'=>$cantidad,
            'total'=>$total,
            'adicionales'=>$adicionales,
            'link'=>$link
        ]);
    }
    #[Route('/paypal/booking', name: 'paypal_return_booking')]
    public function paypal_return_booking(Request $request,MailerInterface $mailer): Response
    {
        $ordersId = $request->get('token');
        $payerID = $request->get('PayerID');
        if(!isset($ordersId) || empty($ordersId)) return $this->redirectToRoute('app_inicio');
        $pago = $this->em->getRepository(PayPalPago::class)->findOneBy(['ordersId'=>$ordersId,'estado'=>'PAYER_ACTION_REQUIRED']);
        if(!isset($pago) || empty($pago)) return $this->redirectToRoute('app_inicio');
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        if(!isset($pago) || empty($pago)){
            return $this->redirectToRoute('app_inicio');
        }else{
            $credenciales = $pago->getCredencialesPayPal();
        }
        $token = null;
        $accesToken = $credenciales->getAccessToken();
        if($accesToken) {
            $fechavence = \DateTime::createFromImmutable($credenciales->getUpdatedAt());
            $expirein = $credenciales->getExpiresIn() . ' seconds';
            date_add($fechavence, date_interval_create_from_date_string($expirein));
            if(new \DateTime() >= $fechavence){
                $token = $this->getTokenPaypal($credenciales->getClientId(),$credenciales->getClientSecret())['token'];
            }
        }else{
            $token = $this->getTokenPaypal($credenciales->getClientId(),$credenciales->getClientSecret())['token'];
        }
        if(isset($token) && !empty($token) && isset($token['access_token']) && !empty($token['access_token'])){
            $credenciales->setAccessToken($token['access_token']);
            $credenciales->setTokenType($token['token_type']);
            $credenciales->setAppId($token['app_id']);
            $credenciales->setExpiresIn(intval($token['expires_in']));
            $credenciales->setNonce($token['nonce']);
            $credenciales->setScope($token['scope']);
            $this->em->persist($credenciales);
            $this->em->flush();
        }
        $url = 'https://api.sandbox.paypal.com/v2/checkout/orders/'.$ordersId.'/capture';
        $ch = curl_init();
        $autorization = 'Bearer ' . $credenciales->getAccessToken() ;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'content-type: application/json',
            'Authorization:' . $autorization
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $response = curl_exec($ch);
        $total=0;
        if (curl_errno($ch)) {
        } else {
            $decodedResponse = json_decode($response, true);
            if ($decodedResponse !== null) {
                if(isset($decodedResponse['status']) && $decodedResponse['status']==='COMPLETED'){
                    foreach ($decodedResponse['purchase_units'] as $pagos){
                        foreach ($pagos['payments']['captures'] as $captures){
                            $datetime = new \DateTime();
                            $detallepagoDB = $this->em->getRepository(DetallePagoPayPal::class)->findOneBy(['id'=>$captures['id']]);
                            if(!isset($detallepagoDB) || empty($detallepagoDB)){
                                $detallepagoDB = new DetallePagoPayPal();
                                $detallepagoDB->setCaptureId($captures['id']);
                                $datetime->setTimestamp(strtotime($captures['create_time']));
                                $detallepagoDB->setCreatedAt($datetime);
                                $detallepagoDB->setPayPalPago($pago);
                            }
                            $datetime->setTimestamp(strtotime($captures['update_time']));
                            $detallepagoDB->setUpdatedAt($datetime);
                            $aux = [];
                            foreach ($captures['seller_receivable_breakdown'] as $key => $detallepago) {
                                $aux[$key]=$detallepago['value'];
                                if($captures['status'] === 'COMPLETED' && $key === 'gross_amount')$total += $detallepago['value'];
                            }
                            $detallepagoDB->setSellerReceivableBreakdown($aux);
                            $this->em->persist($detallepagoDB);
                            $this->em->flush();
                        }
                    }
                }
            }
        }
        curl_close($ch);
        if($total >= $pago->getTotal() ) {
            $pago->setEstado('COMPLETED');

            $pago->getSolicitudReserva()->setEstado($this->em->getRepository(EstadoReserva::class)->find(2));
            $cantidad = count($pago->getSolicitudReserva()->getInChargeOfArray()) + 1;

            $administradores = $this->em->getRepository(Usuario::class)->obtenerUsuariosPorRol('ROLE_ADMIN');
            \App\Services\notificacion::enviarMasivo($administradores, $pago->getSolicitudReserva()->getName() . ' reservó (' . $cantidad .') "'. $pago->getSolicitudReserva()->getBooking()->getNombre().'".', 'Nueva reserva', $this->generateUrl('app_administrador_booking', ['id' => $pago->getSolicitudReserva()->getBooking()->getId()]));
            mailerServer::enviarPagoAprobadoReserva($this->em,$mailer,$pago->getSolicitudReserva(),$this->generateUrl('app_status_booking',['tokenId'=> $pago->getSolicitudReserva()->getLinkDetalles(),'id'=>$pago->getSolicitudReserva()->getId()]));
            $this->em->persist($pago->getSolicitudReserva());
            $this->em->persist($pago);
            $this->em->flush();
        }
        return $this->render('pago/returnBooking.html.twig', [
            'controller_name' => 'PayPalController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'pago'=>$pago,
            'linkDetalles'=> $this->generateUrl('app_status_booking',['tokenId'=>$pago->getSolicitudReserva()->getLinkDetalles(),'id'=>$pago->getSolicitudReserva()->getId()])
        ]);
    }
    private function getTokenPaypal($client_id,$client_secret){
        $token = null;
        $respuesta = "success";
        $url = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
        $autorization = 'Basic ' . base64_encode($client_id . ':' . $client_secret);
        $params = [
            'grant_type' => 'client_credentials'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'content-type: application/x-www-form-urlencoded',
            'Authorization:' . $autorization
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $respuesta = 'Error: ' . curl_error($ch);
        } else {
            $token = json_decode($response, true);
            if ($token !== null) {

            } else {
                $respuesta = 'Error: ' . json_encode($response);
            }
        }
        curl_close($ch);
        $r = ['token'=>$token,'respuesta'=>$respuesta];
        return $r;
    }
}
