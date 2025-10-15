<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\BookingPartner;
use App\Entity\CredencialesMercadoPago;
use App\Entity\CredencialesPayPal;
use App\Entity\Lenguaje;
use App\Entity\MercadoPagoPago;
use App\Entity\Moneda;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\PreguntaFrecuente;
use App\Entity\RespuestaMensaje;
use App\Entity\SolicitudReserva;
use App\Entity\TraduccionBooking;
use App\Entity\TraduccionPlataforma;
use App\Entity\TraduccionPreguntaFrecuente;
use App\Form\BookingType;
use App\Form\CredencialesMercadoPagoType;
use App\Form\CredencialesPayPalType;
use App\Form\PlataformaType;
use App\Form\PreguntaFrecuenteType;
use App\Form\RespuestaMensajeType;
use App\Form\TraduccionBookingType;
use App\Form\TraduccionPlataformaType;
use App\Form\TraduccionPreguntaFrecuenteType;
use App\Services\LanguageService;
use App\Services\MercadoPagoOnboardingService;
use App\Services\PartnerInvitationService;
use App\Services\notificacion;
use Doctrine\ORM\EntityManagerInterface;
use PaypalPayoutsSDK;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        'dashboard'=>false,
        's_preguntas'=>false,
        'partners'=>false,
        'balance'=>false
    ];

    private $em;
    public function __construct(
        EntityManagerInterface $em,
        private readonly PartnerInvitationService $partnerInvitationService,
        private readonly MercadoPagoOnboardingService $mercadoPagoOnboarding
    )
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

            if($mime==='image/png' || $mime==='image/ico' || $mime==='image/x-icon' || $mime==='image/vnd.microsoft.icon'){
                try {
                    $file->move(
                        $this->getParameter($path),
                        $newFilename
                    );
                } catch (FileException $e) {
                    return ['filename'=> "",'upload'=>false];
                }
            }else{
                switch($mime){
                    case 'image/jpeg':
                        $imagen = imagecreatefromjpeg($file);
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
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $this->adminMenu['dashboard'] = true;
        return $this->render('administrador/dashboard.html.twig', [
            'controller_name' => 'AdministradorController',
            'idiomas'=>$idiomas,
            'usuario'=>$this->getUser(),
            'idiomaPlataforma'=>$idioma,
            'plataforma'=>$plataforma,
            'menu'=>$this->adminMenu,

        ]);
    }
    #[Route('/administrador/mensajes', name: 'app_administrador')]
    public function index(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['mensajes'] = true;
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        return $this->render('administrador/index.html.twig', [
            'controller_name' => 'AdministradorController',
            'sinrespuesta'=>[['id'=>233,'email'=>'juanquiross@gmail.com','getCreatedAt'=>new \DateTime(),'getRespuestasMensaje'=>[],'']],
            'mensajes'=>[['id'=>233,'email'=>'juanquiross@gmail.com','getCreatedAt'=>new \DateTime(),'getRespuestasMensaje'=>[],'']],
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'plataforma'=>$plataforma
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
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        return $this->render('administrador/mensaje.html.twig', [
            'controller_name' => 'AdministradorController',
            'mensaje'=>$mensaje,
            'mensaje_respuesta_form'=>$mensaje_respuesta_form,
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'plataforma'=>$plataforma

        ]);
    }
    #[Route('/administrador/traslados', name: 'app_reservas')]
    public function app_reservas(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['traslados'] = true;
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        return $this->render('administrador/traslados.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'plataforma'=>$plataforma
        ]);
    }
    #[Route('/administrador/bookings', name: 'app_administrador_bookings')]
    public function app_administrador_bookings(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $this->adminMenu['reservas'] = true;
        $servicios = $this->em->getRepository(Booking::class)->findAll();
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        return $this->render('administrador/reservas.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'servicios'=>$servicios,
            'plataforma'=>$plataforma
        ]);
    }
    #[Route('/administrador/notificaciones', name: 'app_administrador_notificaciones')]
    public function app_administrador_notificaciones(Request $request): Response
    {
    $idiomas = LanguageService::getLenguajes($this->em);
    $idioma = LanguageService::getLenguaje($this->em,$request);
    $this->adminMenu['notificaciones'] = true;
    $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

    return $this->render('administrador/notificaciones.html.twig', [
        'controller_name' => 'Notificaciones Administrador',
        'usuario'=>$this->getUser(),
        'menu'=>$this->adminMenu,
        'idiomas'=>$idiomas,
        'idiomaPlataforma'=>$idioma,
        'plataforma'=> $plataforma,
        'vapidPublicKey' => notificacion::getPublicKey(),
    ]);
}
    #[Route('/administrador/booking/{id}', name: 'app_administrador_booking', options: ['expose'=>true])]
    public function app_administrador_booking(Booking $booking = null, Request $request): Response
    {
        if(!isset($booking) || empty($booking) ) return $this->redirectToRoute('app_administrador_bookings');
        $this->adminMenu['reservas'] =   true;
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $contenido = $request->query->all();
        $fechafiltro = null;
        if(isset($contenido) && isset($contenido['ff']) && !empty($contenido['ff']))$fechafiltro = $contenido['ff'];
        $reservas=[];
        $personas=[];
        $reservas['pagadas'] = $this->em->getRepository(SolicitudReserva::class)->reservas($booking->getId(),2,$fechafiltro);
        $reservas['pendientes'] = $this->em->getRepository(SolicitudReserva::class)->reservas($booking->getId(),1,$fechafiltro);
        $reservas['canceladas'] = $this->em->getRepository(SolicitudReserva::class)->reservas($booking->getId(),3,$fechafiltro);


        $personas['pendientes'] = $this->em->getRepository(SolicitudReserva::class)->solicitudesDeBooking($booking->getId(),1,$fechafiltro);
        $personas['pagadas'] = $this->em->getRepository(SolicitudReserva::class)->solicitudesDeBooking($booking->getId(),2,$fechafiltro);
        $personas['canceladas'] = $this->em->getRepository(SolicitudReserva::class)->solicitudesDeBooking($booking->getId(),3,$fechafiltro);



        return $this->render('administrador/detallesReservasBooking.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'plataforma'=>$plataforma,
            'idiomaPlataforma'=>$idioma,
            'booking'=>$booking,
            'fechaFiltro'=>$fechafiltro,
            'reservas'=>$reservas,
            'personas'=>$personas
        ]);
    }
    #[Route('/administrador/servicio/booking', name: 'app_service_booking')]
    public function app_service_booking(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $servicios = $this->em->getRepository(Booking::class)->findAll();
        $this->adminMenu['s_reservas'] = true;
        return $this->render('administrador/service_booking.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario'=>$this->getUser(),
            'plataforma'=>$plataforma,
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'servicios'=>$servicios
        ]);
    }

    #[Route('/administrador/partners', name: 'app_admin_partners')]
    public function partners(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $this->adminMenu['partners'] = true;
        $partners = $this->em->getRepository(BookingPartner::class)->findAll();

        return $this->render('administrador/partners.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario' => $this->getUser(),
            'menu' => $this->adminMenu,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'plataforma' => $plataforma,
            'partners' => $partners,
        ]);
    }

    #[Route('/administrador/balance', name: 'app_admin_balance')]
    public function balance(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $this->adminMenu['balance'] = true;

        $credenciales = $plataforma?->getCredencialesMercadoPago();
        $cuenta = null;
        $balance = null;
        $credencialesActualizadas = false;

        if ($credenciales instanceof CredencialesMercadoPago && $credenciales->getAccessToken()) {
            try {
                if ($this->mercadoPagoOnboarding->ensureValidAccessToken($credenciales)) {
                    $credencialesActualizadas = true;
                }

                $cuenta = $this->mercadoPagoOnboarding->syncAccountInformation($credenciales);
                $balance = $this->mercadoPagoOnboarding->fetchBalance($credenciales);
                $credencialesActualizadas = true;
            } catch (\Throwable $exception) {
                $this->addFlash('error', 'No se pudo actualizar la información de Mercado Pago: ' . $exception->getMessage());
            }
        }

        if ($credencialesActualizadas && $credenciales instanceof CredencialesMercadoPago) {
            $this->em->persist($credenciales);
            $this->em->flush();
        }

        $pagos = $this->em->getRepository(MercadoPagoPago::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.solicitudReserva', 'sr')->addSelect('sr')
            ->leftJoin('sr.booking', 'b')->addSelect('b')
            ->leftJoin('b.bookingPartner', 'bp')->addSelect('bp')
            ->leftJoin('bp.Usuario', 'u')->addSelect('u')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $totalComision = 0.0;
        foreach ($pagos as $pago) {
            if ($pago instanceof MercadoPagoPago && $pago->getStatus() === 'approved') {
                $totalComision += (float) ($pago->getApplicationFee() ?? 0.0);
            }
        }

        $puedeConectar = $credenciales instanceof CredencialesMercadoPago
            && $credenciales->getClientId()
            && $credenciales->getClientSecret();

        return $this->render('administrador/balance.html.twig', [
            'controller_name' => 'AdministradorController',
            'usuario' => $this->getUser(),
            'menu' => $this->adminMenu,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'plataforma' => $plataforma,
            'credencialesMercadoPago' => $credenciales,
            'cuentaMercadoPago' => $cuenta,
            'balanceMercadoPago' => $balance,
            'puedeConectarMercadoPago' => $puedeConectar,
            'pagosMercadoPago' => $pagos,
            'totalComisionMercadoPago' => $totalComision,
        ]);
    }

    #[Route('/administrador/mercadopago/conectar', name: 'app_admin_mercadopago_connect')]
    public function mercadopagoConnect(Request $request): Response
    {
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $credenciales = $plataforma?->getCredencialesMercadoPago();

        if (!$credenciales instanceof CredencialesMercadoPago || !$credenciales->getClientId() || !$credenciales->getClientSecret()) {
            $this->addFlash('error', 'Configurá el Client ID y Client Secret antes de vincular Mercado Pago.');

            return $this->redirectToRoute('app_admin_configuraciones');
        }

        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('mp_admin_state', $state);

        try {
            $authorizationUrl = $this->mercadoPagoOnboarding->createAuthorizationUrl(
                $credenciales,
                $this->generateUrl('app_admin_mercadopago_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
                $state,
                ['offline_access', 'read', 'write']
            );
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'No se pudo generar el enlace de autorización: ' . $exception->getMessage());

            return $this->redirectToRoute('app_admin_balance');
        }

        return $this->redirect($authorizationUrl);
    }

    #[Route('/administrador/mercadopago/callback', name: 'app_admin_mercadopago_callback')]
    public function mercadopagoCallback(Request $request): Response
    {
        $session = $request->getSession();
        $expectedState = $session->get('mp_admin_state');
        $session->remove('mp_admin_state');

        $state = $request->query->get('state');
        if (!$state || !$expectedState || $state !== $expectedState) {
            $this->addFlash('error', 'El proceso de autorización caducó o es inválido.');

            return $this->redirectToRoute('app_admin_balance');
        }

        if ($request->query->has('error')) {
            $this->addFlash('error', 'Mercado Pago rechazó la vinculación: ' . $request->query->get('error_description', '')); 

            return $this->redirectToRoute('app_admin_balance');
        }

        $authorizationCode = $request->query->get('code');
        if (!$authorizationCode) {
            $this->addFlash('error', 'Mercado Pago no devolvió el código de autorización.');

            return $this->redirectToRoute('app_admin_balance');
        }

        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $credenciales = $plataforma?->getCredencialesMercadoPago();

        if (!$credenciales instanceof CredencialesMercadoPago) {
            $this->addFlash('error', 'La plataforma no tiene credenciales configuradas.');

            return $this->redirectToRoute('app_admin_balance');
        }

        try {
            $this->mercadoPagoOnboarding->exchangeAuthorizationCode(
                $credenciales,
                (string) $authorizationCode,
                $this->generateUrl('app_admin_mercadopago_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            $this->mercadoPagoOnboarding->syncAccountInformation($credenciales);

            $this->em->persist($credenciales);
            if ($plataforma instanceof Plataforma) {
                $this->em->persist($plataforma);
            }
            $this->em->flush();

            $this->addFlash('success', 'Cuenta de Mercado Pago conectada correctamente.');
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'No se pudo vincular Mercado Pago: ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_admin_balance');
    }

    #[Route('/administrador/mercadopago/desconectar', name: 'app_admin_mercadopago_disconnect', methods: ['POST'])]
    public function mercadopagoDisconnect(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_mp_disconnect', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $credenciales = $plataforma?->getCredencialesMercadoPago();

        if ($credenciales instanceof CredencialesMercadoPago) {
            $credenciales->clearTokens();
            $this->em->persist($credenciales);
            $this->em->flush();

            $this->addFlash('success', 'Se desconectó Mercado Pago de la plataforma.');
        }

        return $this->redirectToRoute('app_admin_balance');
    }

    #[Route('/administrador/partner/{id}/estado', name: 'app_admin_partner_estado', methods: ['POST'])]
    public function updatePartnerStatus(Request $request, BookingPartner $bookingPartner): Response
    {
        if (!$this->isCsrfTokenValid('partner_status_'.$bookingPartner->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        $action = $request->request->get('action', 'update');
        $comision = $request->request->get('comision');

        if ($comision !== null && $comision !== '') {
            if (!is_numeric($comision)) {
                $this->addFlash('error', 'La comisión debe ser un número válido.');
                return $this->redirectToRoute('app_admin_partners');
            }
            $bookingPartner->setComisionPlataforma((float) $comision);
        }

        $usuario = $bookingPartner->getUsuario();
        $roles = $usuario->getRoles();

        if ($action === 'approve') {
            $bookingPartner->setHabilitado(true);
            if (!in_array('ROLE_PARTNER', $roles, true)) {
                $roles[] = 'ROLE_PARTNER';
            }
            $this->addFlash('success', 'Partner habilitado correctamente.');
        } elseif ($action === 'disable') {
            $bookingPartner->setHabilitado(false);
            $roles = array_values(array_filter($roles, static fn (string $role) => $role !== 'ROLE_PARTNER'));
            $this->addFlash('success', 'Partner deshabilitado.');
        } else {
            $this->addFlash('success', 'Datos del partner actualizados.');
        }

        $usuario->setRoles(array_values(array_unique($roles)));

        $this->em->persist($bookingPartner);
        $this->em->persist($usuario);
        $this->em->flush();

        return $this->redirectToRoute('app_admin_partners');
    }
    #[Route('/administrador/FAKs', name: 'app_plataforma_preguntas')]
    public function app_plataforma_preguntas(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $preguntas = $this->em->getRepository(PreguntaFrecuente::class)->findAll();
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $this->adminMenu['s_preguntas'] = true;
        $partnerInviteLink = $this->generateUrl(
            'app_register_partner_invite',
            ['code' => $this->partnerInvitationService->generateInviteCode()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return $this->render('administrador/FAKs.html.twig', [
            'controller_name' => 'AdministradorController',
            'plataforma'=>$plataforma,
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'preguntas'=>$preguntas,
            'partnerInviteLink' => $partnerInviteLink,
        ]);
    }

    #[Route('/administrador/FAKs/quitar/pregunta', name: 'app_admin_remove_fak',methods: ['POST'], options: ['expose'=>true])]
    public function app_admin_remove_fak(Request $request): Response
    {
        $id = json_decode($request->getContent())->preguntaId;
        $ret = false;
        if(isset($id) && !empty($id)) {
            $pregunta = $this->em->getRepository(PreguntaFrecuente::class)->find($id);
            $this->em->remove($pregunta);
            $this->em->flush();
            $ret = true;
        }
        return new JsonResponse(['eliminado'=>$ret,'id'=>$id,],200);
    }


    #[Route('/administrador/servicio/booking/traduccion/{codLenguaje}/{id}', defaults:["id"=> 0], name: 'app_admin_traduccion_booking')]
    public function app_admin_traduccion_booking(string $codLenguaje,Booking $booking = null,Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $lenguaje = $this->em->getRepository(Lenguaje::class)->findOneBy(['codigo' => $codLenguaje]);

        if (!isset($lenguaje) || empty($lenguaje) || !isset($booking) || empty($booking)) return $this->redirectToRoute('app_service_booking');
        $this->adminMenu['s_reservas'] = true;

        $traduccion = null;

        if($booking->getLenguaje()->getId() != $lenguaje->getId()) {

            $traduccion = $booking->getTraduccionSiExiste($lenguaje);

            if (!isset($traduccion) || empty($traduccion)) {
                $traduccion = new TraduccionBooking();
                $traduccion->setLenguaje($lenguaje);
                $traduccion->setBooking($booking);
            }

        }else{
            //redirect to edit boooking
            return $this->redirectToRoute('app_new_service_booking',['id'=>$booking->getId()]);
        }

        $formulario = $this->createForm(TraduccionBookingType::class,$traduccion);
        $formulario->handleRequest($request);
        if($formulario->isSubmitted() && $formulario->isValid()){
            $traduccion->setLenguaje($lenguaje);
            $this->em->persist($traduccion);
            $this->em->flush();
            return $this->redirectToRoute('app_service_booking');
        }
        return $this->render('administrador/traduccionBooking.html.twig', [
            'controller_name' => 'Preguntas de plataforma',
            'plataforma'=>$plataforma,
            'booking'=>$booking,

            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'lenguajeFormulario'=>$lenguaje,

            'form'=>$formulario
        ]);
    }


    #[Route('/administrador/FAKs/agregareditar/{codLenguaje}/{id}', defaults:["id"=> 0], name: 'app_admin_add_edit_fak')]
    public function app_admin_add_edit_fak(string $codLenguaje,PreguntaFrecuente $pregunta = null,Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $editarPregunta = false;
        $lenguaje = $this->em->getRepository(Lenguaje::class)->findOneBy(['codigo' => $codLenguaje]);
        $traduccion = null;
        if (!isset($lenguaje) || empty($lenguaje)) return $this->redirectToRoute('app_inicio');
        $this->adminMenu['s_preguntas'] = true;


        if (isset($pregunta) && !empty($pregunta)) {
            $traduccion = $pregunta->getTraduccionSiExiste($lenguaje);
            if (isset($traduccion) && !empty($traduccion)) {
                if ($traduccion->getId() == null) $editarPregunta = true;
            } else {
                $traduccion = new TraduccionPreguntaFrecuente();
                $traduccion->setLenguaje($lenguaje);
                $traduccion->setPreguntaFrecuente($pregunta);
            }
        }else{
            $pregunta = new PreguntaFrecuente();
            $pregunta->setLenguajeDefecto($lenguaje);
            $editarPregunta = true;
        }

            $formularioTraduccion = $this->createForm(TraduccionPreguntaFrecuenteType::class, $traduccion);
            $formularioTraduccion->handleRequest($request);
            if ($formularioTraduccion->isSubmitted() && $formularioTraduccion->isValid() && !$editarPregunta) {

                $this->em->persist($traduccion);
                $this->em->flush();
                return $this->redirectToRoute('app_plataforma_preguntas', [], 302);
            }

            $formularioPregunta = $this->createForm(PreguntaFrecuenteType::class, $pregunta);
            $formularioPregunta->handleRequest($request);
            if ($formularioPregunta->isSubmitted() && $formularioPregunta->isValid() && $editarPregunta) {
                $this->em->persist($pregunta);
                $this->em->flush();
                return $this->redirectToRoute('app_plataforma_preguntas', [], 302);
            }





        return $this->render('administrador/formFAKs.html.twig', [
            'controller_name' => 'Preguntas de plataforma',
            'plataforma'=>$plataforma,
            'pregunta'=>$pregunta,
            'editarPregunta'=>$editarPregunta,
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'lenguajeFormulario'=>$lenguaje,
            'formularioPregunta'=>$formularioPregunta,
            'formularioTraduccion'=>$formularioTraduccion,
            'partnerInviteLink' => $this->generateUrl(
                'app_register_partner_invite',
                ['code' => $this->partnerInvitationService->generateInviteCode()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
    }


    #[Route('/administrador/configuraciones', name: 'app_admin_configuraciones')]
    public function app_admin_configuraciones(Request $request,SluggerInterface $slugger): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $servicios = $this->em->getRepository(Booking::class)->findAll();
        $traducciones = $this->em->getRepository(TraduccionPlataforma::class)->findAll();
        $this->adminMenu['configuraciones'] = true;

        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $plataforma->backLogo = $plataforma->getLogo();
        $plataforma->backIcono = $plataforma->getIcono();
        //Plataforma

        $formularioPlataforma = $this->createForm(PlataformaType::class,$plataforma);
        $formularioPlataforma->handleRequest($request);
        if($formularioPlataforma->isSubmitted() && $formularioPlataforma->isValid()){


            $logo = $formularioPlataforma->get('logo')->getData();
            if(isset($logo) && !empty($logo)){
                $upload = $this->upload($logo,'img',$slugger);
                if($upload['upload']){
                    $plataforma->setLogo($upload['filename']);
                }
            }

            if((isset($plataforma->backLogo) && !empty($plataforma->backLogo) ) && ($plataforma->getLogo() == null || count(explode('.',$plataforma->getLogo())) <= 1 )){
                $plataforma->setLogo($plataforma->backLogo);
            }

            $icono = $formularioPlataforma->get('icono')->getData();
            if(isset($icono) && !empty($icono)){
                $upload = $this->upload($icono,'basedir',$slugger);
                if($upload['upload']){
                    $plataforma->setIcono($upload['filename']);
                }
            }

            if((isset($plataforma->backIcono) && !empty($plataforma->backIcono) ) && ($plataforma->getIcono() == null || count(explode('.',$plataforma->getIcono())) <= 1 )){
                $plataforma->setIcono($plataforma->backIcono);
            }







            $this->em->persist($plataforma);
            $this->em->flush();
            $this->redirectToRoute('app_admin_configuraciones',[],302);

        }



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
            return $this->redirectToRoute('app_admin_configuraciones',[],302);

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
                return $this->redirectToRoute('app_admin_configuraciones',[],302);
            }
        }
        return $this->render('administrador/configuraciones.html.twig', [
            'controller_name' => 'Configuración de plataforma',
            'plataforma'=>$plataforma,
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'servicios'=>$servicios,
            'formularioCredencialesPayPal'=>$formularioPayPal,
            'formularioCredencialesMp'=>$formularioMercadoPago,
            'traduccionesPlataforma'=>$traducciones,
            'formularioPlataforma'=>$formularioPlataforma
        ]);
    }
    #[Route('/administrador/configuraciones/traduccion/plataforma/{codLenguaje}/{keyValue}', name: 'app_admin_traduccion_plataforma')]
    public function app_admin_traduccion_plataforma(string $codLenguaje,string $keyValue,Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $lenguaje = $this->em->getRepository(Lenguaje::class)->findOneBy(['codigo'=>$codLenguaje]);
        if(!isset($lenguaje) || empty($lenguaje)) return $this->redirectToRoute('app_inicio');
        $this->adminMenu['configuraciones'] = true;

        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $traduccion = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['lenguaje'=>$lenguaje,'key_name'=>$keyValue]);
        if(!isset($traduccion)||empty($traduccion)){
            $traduccion = new TraduccionPlataforma();
            $traduccion->setLenguaje($lenguaje);
            $traduccion->setPlataforma($plataforma);
            $traduccion->setKeyName($keyValue);
        }

        $formularioTraduccion = $this->createForm(TraduccionPlataformaType::class,$traduccion);
        $formularioTraduccion->handleRequest($request);
        if($formularioTraduccion->isSubmitted() && $formularioTraduccion->isValid()){
            $this->em->persist($traduccion);
            $this->em->flush();
            return $this->redirectToRoute('app_admin_configuraciones',[],302);
        }
        return $this->render('administrador/plataforma/traduccion.html.twig', [
            'controller_name' => 'Configuración de plataforma',
            'usuario'=>$this->getUser(),
            'menu'=>$this->adminMenu,
            'plataforma'=>$plataforma,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'form'=>$formularioTraduccion,
            'traduccion'=>$traduccion
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

    #[Route('/administrador/servicio/booking/quitar/fecha/{id}', name: 'app_service_booking_de_fecha',methods: ['POST'], options: ['expose'=>true])]
    public function app_service_booking_de_fecha(Booking $booking,Request $request): Response
    {
        $fecha = json_decode($request->getContent())->fechaString;


        $ret = false;
        if(isset($fecha) && !empty($fecha) && isset($booking) && !empty($booking)) {
            $fechas = json_decode($booking->getFechasdelservicio());
            if(isset($fechas) && !empty($fechas) && count($fechas)>0){
                $auxFechas=[];
                foreach ($fechas as $f){
                    if($f->fecha != $fecha){
                        $auxFechas[]=$f;
                    }
                }
                $booking->setFechasdelservicio(json_encode($auxFechas));
                $this->em->persist($booking);
                $this->em->flush();
                $ret = true;
            }

        }
        return new JsonResponse(['eliminado'=>$ret],200);
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
            $fechas = json_decode($booking->getFechasdelservicio());
            if(isset($fechas) && !empty($fechas)){
                $fechasAux=[];
                foreach ($fechas as $fech){
                    $filtro = array_filter($fechasAux, function ($obj) use ($fech) {return $obj->fecha == $fech->fecha;});
                    if(isset($filtro) && !empty($filtro)){
                        $fechasAux[array_key_first($filtro)]->cantidad += $fech->cantidad;
                    }else{
                        $fechasAux []=$fech;
                    }
                    if($fechasAux[array_key_last($fechasAux)]->cantidad < 0) $fechasAux[array_key_last($fechasAux)]->cantidad = 0;
                }
                $booking->setFechasdelservicio(json_encode($fechasAux));
            }
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
            'plataforma'=>$plataforma,
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
                        $imagenes = $booking->getImagenesArray();
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
