<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\EstadoReserva;
use App\Entity\Lenguaje;
use App\Entity\PayPalPago;
use App\Entity\Plataforma;
use App\Entity\Reserva;
use App\Entity\servicioReserva;
use App\Entity\SolicitudReserva;
use App\Form\ReservaType;
use App\Form\SolicitudReservaType;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InicioController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'app_inicio')]
    public function index(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $bookings = $this->em->getRepository(Booking::class)->obtenerVálidos(new \DateTime());

        return $this->render('inicio/index.html.twig', [
            'controller_name' => 'InicioController',
            'idiomas'=>$idiomas,
            'bookings'=>$bookings,
            'idiomaPlataforma'=>$idioma
        ]);
    }
    #[Route('/reserva/{id}', name: 'app_reserva')]
    public function reserva(Booking $booking,Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $fechasDatetime = null;
        if(!isset($booking) || empty($booking) || !$booking->isHabilitado()){
            return $this->redirect('app_inicio');
        }
        $fechas = $booking->getFechasdelservicio();
        $formulario = $booking->getFormRequerido();
        if(isset($formulario)&& !empty($formulario)){
            $formulario = json_decode($formulario);
        }
        if(isset($fechas) && !empty($fechas)){
            $fechasDatetime = [];
            foreach (json_decode($fechas) as $fechaDatetime){
                $fechasDatetime []= \DateTime::createFromFormat('Y-m-d\TH:i', $fechaDatetime->fecha);
            }
        }
        $reserva = new SolicitudReserva();
        $reserva->setBooking($booking);
        $reservaForm = $this->createForm(SolicitudReservaType::class,$reserva);
        $reservaForm->handleRequest($request);
        if($reservaForm->isSubmitted() && $reservaForm->isValid()){
            $fechasarray =json_decode($fechas);
            if(isset($fechasarray) && !empty($fechasarray) && count($fechasarray) >= 1){
                $fecha = '' . ($reservaForm->get('fechaSeleccionadaString')->getData());
                $specific_value = str_replace(' ','T',$fecha);
                $filtro = array_filter($fechasarray, function ($obj) use ($specific_value) {return $obj->fecha == $specific_value;});
                if( count($filtro) === 1 ){
                    $reserva->setFechaSeleccionada(\DateTime::createFromFormat( 'Y-m-d H:i', $fecha));
                }else{
                    $reservaForm->addError(new FormError('Error en fecha seleccionada'));
                };
            }

            if(count($formulario)>0){
                $formRequerido = json_decode(($reservaForm->get('form_required')->getData()));
                if($this->comprobarFormularioRequerido($formRequerido,$formulario))$reservaForm->addError(new FormError('Todos los campos son obligatorios'));
                $reservas = $reserva->getInChargeOf();
                if(isset($reservas) && !empty($reservas) && json_validate($reservas)){
                    $reservas = json_decode($reservas) ;
                    foreach ($reservas as $aux){
                        if($this->comprobarFormularioRequerido($aux->form_required,$formulario))$reservaForm->addError(new FormError('Complete los datos de las reservas adicionales'));
                    }
                }
            }
            if($reservaForm->isValid()) {
                $reserva->setEstado($this->em->getRepository(EstadoReserva::class)->find(1));
                $this->em->persist($reserva);
                $this->em->flush();
                return $this->redirectToRoute('paypal_pay_booking',['id'=>$reserva->getId()]);

            }
        }
        return $this->render('reserva/index.html.twig', [
            'controller_name' => 'InicioController',
            'booking'=>$booking,
            'reservaForm'=>$reservaForm,
            'idiomas'=>$idiomas,
            'imagenes'=>json_decode($booking->getImagenes()),
            'idiomaPlataforma'=>$idioma,
            'fechas'=>$fechasDatetime,
            'formulario'=>$formulario
        ]);
    }
    private function comprobarFormularioRequerido($datosdeusuario,$formulario):bool
    {
        $errorForm = false;
        if(isset($datosdeusuario) && !empty($datosdeusuario) && count($datosdeusuario)==count($formulario)){
            foreach ($datosdeusuario as $form){if(!isset($form->value) || empty($form->value)) $errorForm = true;}
        }else{$errorForm = true;}
        return $errorForm;
    }
    #[Route('/buscar/booking', name: 'app_inicio_search', methods: ['POST'], options: ['expose'=>true])]
    public function app_inicio_search(Request $request): Response
    {
        $bookings = null;
        $content = $request->get('fechafiltro');
        if(isset($content) && !empty($content)){
            $bookings = $this->em->getRepository(Booking::class)->obtenerVálidos($content);
        }
        $render = $this->render('inicio/bookings.html.twig',[
            'bookings'=>$bookings
            ]);
        return new JsonResponse(['render'=>$render->getContent()],200);
    }


    #[Route('/status/booking/{tokenId}/{id}', defaults:["tokenId"=> '',"id"=>0], name: 'app_status_booking')]
    public function app_status_booking(string $tokenId,SolicitudReserva $solicitudReserva =null , Request $request): Response
    {
        $render=null;
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        if(isset($tokenId) && !empty($tokenId) && isset($solicitudReserva) && !empty($solicitudReserva)){
            $sha = base64_encode($solicitudReserva->getId() . ':'.$solicitudReserva->getEmail());
            if($sha != $tokenId) return $this->redirectToRoute('app_status_booking');
            $pago = null;
            if($solicitudReserva->getEstado()->getId() == 1){
                $pago = $this->em->getRepository(PayPalPago::class)->findOneBy(['solicitudReserva'=>$solicitudReserva->getId(),'estado'=>'PAYER_ACTION_REQUIRED']);
            }

            $render = $this->renderView('inicio/status/detallesSolicitudBooking.html.twig',[
                'booking'=>$solicitudReserva,
                'adicionales'=>json_decode($solicitudReserva->getInChargeOf(),true),
                'idiomaPlataforma'=>$idioma,
                'pago'=>$pago,
                'melink'=>$this->generateUrl('app_status_booking',['tokenId'=>$tokenId,'id'=>$solicitudReserva->getId()],false)
            ]);


        }


        return $this->render('inicio/status/booking.html.twig', [
            'controller_name' => 'InicioController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'render'=>$render
        ]);
    }
    #[Route('/buscar/status/booking', name: 'app_status_booking_search', methods: ['POST'], options: ['expose'=>true])]
    public function app_status_booking_search(Request $request): Response
    {
        $solicitudes = null;
        $emailSearch = $request->get('emailSearch');
        if(isset($content) && !empty($content)){
            $solicitudes = $this->em->getRepository(SolicitudReserva::class)->findBy(['email'=>$emailSearch]);
        }
        $render = $this->render('inicio/status/plantillaBookings.html.twig',[
            'solicitudes'=>$solicitudes
        ]);
        return new JsonResponse(['render'=>$render->getContent()],200);
    }
}
