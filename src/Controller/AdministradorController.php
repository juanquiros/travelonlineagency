<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\CredencialesMercadoPago;
use App\Entity\CredencialesPayPal;
use App\Entity\Moneda;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\RespuestaMensaje;
use App\Entity\SolicitudReserva;
use App\Form\BookingType;
use App\Form\CredencialesMercadoPagoType;
use App\Form\CredencialesPayPalType;
use App\Form\RespuestaMensajeType;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use PaypalPayoutsSDK;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdministradorController extends AbstractController
{




    private $adminMenu= [
        'notificaciones'=>false,
        'mensajes'=>false,
        'traslados'=>false,
        'reservas'=>false,
        's_traslados'=>false,
        's_reservas'=>false,
        'configuraciones'=>false,
        'home'=>false
    ];

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function upload($file,$path, SluggerInterface $slugger){
        if(isset($file) && !empty($file)){
            $Filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($Filename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            $imgInfo = getimagesize($file);
            $calidad = $file->getSize();
            if($calidad > 1536){ $calidad = 70;}else{$calidad = 100;}
            $mime = $imgInfo['mime'];

            switch($mime){
                case 'image/jpeg':
                    $imagen = imagecreatefromjpeg($file);
                    break;
                case 'image/png':
                    $imagen = imagecreatefrompng($file);
                    break;
                case 'image/gif':
                    $imagen = imagecreatefromgif($file);
                    break;
                default:
                    $imagen = imagecreatefromjpeg($file);
            }

            try {
                imagejpeg($imagen, $this->getParameter($path).'/'.$newFilename,$calidad);

            } catch (FileException $e) {
                return ['filename'=> "",'upload'=>false];
            }
            return ['filename'=> $newFilename,'upload'=>true];
        }
    }



    private function getTokenPaypal($client_id,$client_secret)
    {
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
        return ['token'=>$token,'respuesta'=>$respuesta];
    }


    #[Route('/administrador', name: 'app_administrador_home')]
    public function app_administrador_home(Request $request): Response
    {

        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $credenciales = $plataforma->getCredencialesPayPal();
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
        if(isset($token) && !empty($token) && isset($token['token']['access_token']) && !empty($token['token']['access_token'])){
            $credenciales->setAccessToken($token['token']['access_token']);
            $credenciales->setTokenType($token['token']['token_type']);
            $credenciales->setAppId($token['token']['app_id']);
            $credenciales->setExpiresIn(intval($token['token']['expires_in']));
            $credenciales->setNonce($token['token']['nonce']);
            $credenciales->setScope($token['token']['scope']);
            $this->em->persist($credenciales);
            $this->em->flush();
        }



            $url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders';
            $params = [
                'intent' => "CAPTURE",
                'payment_source' => [
                    'paypal'=>[
                        'experience_context'=>[
                            'payment_method_preference'=>'IMMEDIATE_PAYMENT_REQUIRED',
                            'user_action'=>'PAY_NOW',
                            'return_url'=>'https://localhost:8000',
                            'cancel_url'=>'https://localhost:8000'
                        ]
                    ]
                ],
                'purchase_units' => [[
                    'amount'=>[
                        'currency_code'=>'USD',
                        'value'=>'10.00',
                        'breakdown'=>[
                            'item_total'=>[
                                'currency_code'=>'USD',
                                'value'=>'10.00'
                            ]
                        ]
                    ],
                    'items'=>[[
                        'name'=>'CATAMARAN NOMBRE',
                        'description'=>'DESCRIPCION DEL SERVICIO',
                        'unit_amount'=>[
                            'currency_code'=>'USD',
                            'value'=>'10.00'
                        ],
                        'quantity'=>'1',
                        'sku'=>'HOLAMUNDOID'
                    ]]
                ]]
            ];
            $ch = curl_init();
            $autorization = 'Bearer ' . $credenciales->getAccessToken() ;
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
            if (curl_errno($ch)) {
                $respuesta = 'Curl error: ' . curl_error($ch);
            } else {
                $decodedResponse = json_decode($response, true);
                if ($decodedResponse !== null) {

                    $respuesta = "success";
                } else {
                    $respuesta = $response;
                }
            }
            curl_close($ch);


















        return $this->render('administrador/testpaypal.html.twig', [
            'controller_name' => 'AdministradorController',
            'link'=>$decodedResponse['links'][1]['href']

        ]);
    }
    #[Route('/administrador/mensajes', name: 'app_administrador')]
    public function index(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['mensajes'] = true;
        return $this->render('administrador/index.html.twig', [
            'controller_name' => 'AdministradorController',
            'sinrespuesta'=>[['id'=>233,'email'=>'juanquiross@gmail.com','getCreatedAt'=>new \DateTime(),'getRespuestasMensaje'=>[],'']],
            'mensajes'=>[['id'=>233,'email'=>'juanquiross@gmail.com','getCreatedAt'=>new \DateTime(),'getRespuestasMensaje'=>[],'']],
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma
        ]);
    }
    #[Route('/administrador/mensaje', name: 'app_mensaje')]
    public function app_mensaje(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['mensajes'] = true;
        $mensaje_respuesta = new RespuestaMensaje();
        $mensaje_respuesta_form = $this->createForm(RespuestaMensajeType::class, $mensaje_respuesta);
        $mensaje=[
                'id'=>233,
                'nombre'=>'Juan Quiros',
                'email'=>'juanquiross@gmail.com',
                'mensaje'=>'Este es un mensaje de un cliente',
                'getCreatedAt'=>new \DateTime(),
                'respuestasMensaje'=>[[
                    'usuario'=>['nombre'=>'Juan'],
                    'mensaje'=>'Este es un mensaje de respuesta',
                    'getCreatedAt'=>new \DateTime()
                ]]
        ];
        return $this->render('administrador/mensaje.html.twig', [
            'controller_name' => 'AdministradorController',
            'mensaje'=>$mensaje,
            'mensaje_respuesta_form'=>$mensaje_respuesta_form,
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma

        ]);
    }
    #[Route('/administrador/traslados', name: 'app_reservas')]
    public function app_reservas(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['traslados'] = true;
        return $this->render('administrador/traslados.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma
        ]);
    }
    #[Route('/administrador/bookings', name: 'app_administrador_bookings')]
    public function app_administrador_bookings(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['reservas'] = true;
        $servicios = $this->em->getRepository(Booking::class)->findAll();
        return $this->render('administrador/reservas.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'servicios'=>$servicios
        ]);
    }
    #[Route('/administrador/booking/{id}', name: 'app_administrador_booking')]
    public function app_administrador_booking(Booking $booking = null, Request $request): Response
    {
        if(!isset($booking) || empty($booking) ) return $this->redirectToRoute('app_administrador_bookings');
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['reservas'] =   true;
        $reservas=[];
        $reservas['pagadas'] = $this->em->getRepository(SolicitudReserva::class)->findBy(['estado'=>2,'Booking'=>$booking->getId()]);
        $reservas['pendientes'] = $this->em->getRepository(SolicitudReserva::class)->findBy(['estado'=>1,'Booking'=>$booking->getId()]);
        $reservas['canceladas'] = $this->em->getRepository(SolicitudReserva::class)->findBy(['estado'=>3,'Booking'=>$booking->getId()]);
        $srv = new SolicitudReserva();

        return $this->render('administrador/detallesReservasBooking.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'booking'=>$booking,
            'reservas'=>$reservas
        ]);
    }
    #[Route('/administrador/servicio/booking', name: 'app_service_booking')]
    public function app_service_booking(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $servicios = $this->em->getRepository(Booking::class)->findAll();
        $this->adminMenu['s_reservas'] = true;
        return $this->render('administrador/service_booking.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'servicios'=>$servicios
        ]);
    }
    #[Route('/administrador/configuraciones', name: 'app_admin_configuraciones')]
    public function app_admin_configuraciones(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $servicios = $this->em->getRepository(Booking::class)->findAll();

        $this->adminMenu['configuraciones'] = true;

        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        //MERCADOPAGO CREDENCIALES PLATAFORMA

        $credencialesMercadoPago = $plataforma->getCredencialesMercadoPago();
        if(!isset($credencialesMercadoPago)|| empty($credencialesMercadoPago))$credencialesMercadoPago = new CredencialesMercadoPago();

        $formularioMercadoPago = $this->createForm(CredencialesMercadoPagoType::class,$credencialesMercadoPago);
        $formularioMercadoPago->handleRequest($request);
        if($formularioMercadoPago->isSubmitted() && $formularioMercadoPago->isValid()){
                $this->em->persist($credencialesMercadoPago);
                $plataforma->setCredencialesMercadoPago($credencialesMercadoPago);
                $this->em->persist($plataforma);
                $this->em->flush();
                $this->redirectToRoute('app_admin_configuraciones',[],302);

        }







        //PAYPAL CREDENCIALES PLATAFORMA
        $credencialesPayPalPlataforma = $plataforma->getCredencialesPayPal();
        if(!isset($credencialesPayPalPlataforma)|| empty($credencialesPayPalPlataforma))$credencialesPayPalPlataforma = new CredencialesPayPal();

        $formularioPayPal = $this->createForm(CredencialesPayPalType::class,$credencialesPayPalPlataforma);
        $formularioPayPal->handleRequest($request);
        if($formularioPayPal->isSubmitted() && $formularioPayPal->isValid()){
            $token = $this->getTokenPaypal($formularioPayPal->get('client_id')->getData(),$formularioPayPal->get('client_secret')->getData());
            if(!isset($token['token']['access_token']) || empty($token['token']['access_token']) || $token['token'] == null){
                $formularioPayPal->addError(new FormError('Credenciales Inválidas: '.$token['token']['error_description']));
            }
            if($formularioPayPal->isValid()) {
                $credencialesPayPalPlataforma->setAccessToken($token['token']['access_token']);
                $credencialesPayPalPlataforma->setTokenType($token['token']['token_type']);
                $credencialesPayPalPlataforma->setAppId($token['token']['app_id']);
                $credencialesPayPalPlataforma->setExpiresIn(intval($token['token']['expires_in']));
                $credencialesPayPalPlataforma->setNonce($token['token']['nonce']);
                $credencialesPayPalPlataforma->setScope($token['token']['scope']);

                $this->em->persist($credencialesPayPalPlataforma);
                $plataforma->setCredencialesPayPal($credencialesPayPalPlataforma);
                $this->em->persist($plataforma);
                $this->em->flush();
                $this->redirectToRoute('app_admin_configuraciones',[],302);
            }
        }
        return $this->render('administrador/configuraciones.html.twig', [
            'controller_name' => 'Configuración de plataforma',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'servicios'=>$servicios,
            'formularioCredencialesPayPal'=>$formularioPayPal,
            'formularioCredencialesMp'=>$formularioMercadoPago
        ]);
    }
    #[Route('/administrador/servicio/booking/quitar/precio', name: 'app_service_booking_del_precio',methods: ['POST'], options: ['expose'=>true])]
    public function app_service_booking_del_precio(Request $request): Response
    {
        $id = json_decode($request->getContent())->precioId;
        $ret = false;
        if(isset($id) && !empty($id)) {
            $precio = $this->em->getRepository(Precio::class)->find($id);
            $this->em->remove($precio);
            $this->em->flush();
            $ret = true;
        }
        return new JsonResponse(['eliminado'=>$ret,'id'=>$id,'cont'=>$request->getContent(),'user'=>$request->getUser()],200);
    }
    #[Route('/administrador/servicio/booking/administrar/{id?}', defaults:["id"=> 0], name: 'app_new_service_booking')]
    public function app_new_service_booking(Request $request,Booking $booking = null): Response
    {
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $this->adminMenu['s_reservas'] = true;
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $monedas = $this->em->getRepository(Moneda::class)->findBy(['habilitada'=>true]);
        $monedasArray = [];
        $fechas = [];
        $preciosBooking = [];
        if(isset($monedas) && !empty($monedas)){
            foreach ($monedas as $money){
                $monedasArray []= $money->getMonedaInArray();
            }
        }
        $imgs=[];
        $datos =[];
        if(!isset($booking) || empty($booking)){
            $booking = new Booking();
            $booking->setLenguaje($plataforma->getLanguageDef());
            $booking->setValidoHasta(new \DateTimeImmutable());
            $bookingId = null;
        }else{
            if(!empty($booking->getFormRequerido()))$datos = json_decode($booking->getFormRequerido());
            if(!empty($booking->getImagenes()))$imgs=json_decode($booking->getImagenes());
            if(!empty($booking->getPrecios()))$preciosBooking= $booking->getPrecios();
            if(!empty($booking->getFechasdelservicio()))$fechas= json_decode($booking->getFechasdelservicio());
            $bookingId = $booking->getId();
        }
        $formulario = $this->createForm(BookingType::class,$booking);
        $formulario->handleRequest($request);
        if($formulario->isSubmitted() && $formulario->isValid()){
            $precios = $formulario->get('preciosaux')->getData();
            $this->em->persist($booking);
            if(isset($precios) && !empty($precios)){
                $precios = json_decode($precios);
                foreach ($precios as $precio){
                    $precioNew = null;
                    if(isset($precio->id) && !empty($precio->id) && $precio->id != null){
                        $precioNew = $this->em->getRepository(Precio::class)->findOneBy(['id'=>$precio->id,'booking'=>$booking->getId()]);
                    }
                    if(!isset($precioNew) || empty($precioNew)){
                        $precioNew = new Precio();
                        $precioNew->setBooking($booking);
                    }
                    $moneda = $this->em->getRepository(Moneda::class)->find( intval( $precio->monedaId));
                    $precioNew->setMoneda($moneda);
                    $precioNew->setValor(floatval( $precio->valor));
                    $this->em->persist($precioNew);
                }
            }
            $this->em->flush();
            return $this->redirectToRoute('app_service_booking');
        }

        return $this->render('administrador/new_service_booking.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'bookingid'=>$bookingId,
            'bookingimg'=>$imgs,
            'datos'=>$datos,
            'precios'=>$preciosBooking,
            'fechas'=>$fechas,
            'monedas'=>$monedasArray,
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'form'=>$formulario
        ]);
    }
    #[Route('/administrador/servicio/booking/uploadimagen', name: 'app_uploadimagenbooking',methods: ['POST'], options: ['expose'=>true])]
    public function app_uploadimagenbooking(Request $request,SluggerInterface $slugger): Response
    {
        $data = json_decode($request->get('data'));
        $bookingId = $data->bookingid;
        $isportada = $data->isportada;
        $enform = $data->enform;
        if (isset($enform) && !empty($enform)) $enform = json_decode($enform);
        $flyer = $request->files->get('imagen');
        if(isset($flyer) && !empty($flyer)){
            $aux = [];
            $imagenes=[];
            if(isset($enform) && !empty($enform)) {
                $imagenes = $enform;
            }else{
                if(isset($bookingId) && !empty($bookingId)) {
                    $booking = $this->em->getRepository(Booking::class)->find($bookingId);
                    if (isset($booking) && !empty($booking)) {
                        $imagenes = $booking->getImagenes();
                    }
                }
            }
            if(isset($imagenes) && !empty($imagenes)){
                if(!$isportada){
                    $aux = $imagenes;
                }else{
                    foreach ($imagenes as $imagen ){
                        $imagen->portada = !$isportada;
                        $aux []=$imagen;
                    }
                }
            }else{
                $isportada=true;
            }
            $upload = $this->upload($flyer,'img_booking',$slugger);
            if($upload['upload']){
                $aux[]=['imagen'=>$upload['filename'], 'portada'=>$isportada];
            }
        }
        return new JsonResponse(['files'=>$aux],200);
    }


}
