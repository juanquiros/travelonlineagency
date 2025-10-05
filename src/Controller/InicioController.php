<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\EstadoReserva;
use App\Entity\PayPalPago;
use App\Entity\Plataforma;
use App\Entity\SolicitudReserva;
use App\Entity\TraduccionPlataforma;
use App\Form\SolicitudReservaType;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $usuario = $this->getUser();


        $cfg_bookings['titulo'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:bookings:titulo','lenguaje'=>$idioma->getId()]);
        if(!isset( $cfg_bookings['titulo']) || empty( $cfg_bookings['titulo']))  $cfg_bookings['titulo'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:bookings:titulo']);
        if(!isset( $cfg_bookings['titulo']) || empty( $cfg_bookings['titulo'])){  $cfg_bookings['titulo'] = "Reservas";}else{ $cfg_bookings['titulo'] =  $cfg_bookings['titulo']->getValue();}
        $cfg_bookings['descripcion'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:bookings:descripcion','lenguaje'=>$idioma->getId()]);
        if(!isset( $cfg_bookings['descripcion']) || empty( $cfg_bookings['descripcion']))  $cfg_bookings['descripcion'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:bookings:descripcion']);
        if(!isset( $cfg_bookings['descripcion']) || empty( $cfg_bookings['descripcion'])){  $cfg_bookings['descripcion'] = null;}else{ $cfg_bookings['descripcion'] =  $cfg_bookings['descripcion']->getValue();}

        $cfg_traslados['titulo'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:traslados:titulo','lenguaje'=>$idioma->getId()]);
        if(!isset( $cfg_traslados['titulo']) || empty( $cfg_traslados['titulo']))  $cfg_traslados['titulo'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:traslados:titulo']);
        if(!isset( $cfg_traslados['titulo']) || empty( $cfg_traslados['titulo'])){  $cfg_traslados['titulo'] = "Traslados";}else{ $cfg_traslados['titulo'] =  $cfg_traslados['titulo']->getValue();}
        $cfg_traslados['descripcion'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:traslados:descripcion','lenguaje'=>$idioma->getId()]);
        if(!isset( $cfg_traslados['descripcion']) || empty( $cfg_traslados['descripcion']))  $cfg_traslados['descripcion'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_inicio:traslados:descripcion']);
        if(!isset( $cfg_traslados['descripcion']) || empty( $cfg_traslados['descripcion'])){  $cfg_traslados['descripcion'] = null;}else{ $cfg_traslados['descripcion'] =  $cfg_traslados['descripcion']->getValue();}



        return $this->render('inicio/index.html.twig', [
            'controller_name' => 'InicioController',
            'plataforma'=>$plataforma,
            'idiomas'=>$idiomas,
            'bookings'=>$bookings,
            'idiomaPlataforma'=>$idioma,
            'now'=> new \DateTime(),
            'cfg_bookings'=>$cfg_bookings,
            'cfg_traslados'=>$cfg_traslados,
            'usuario'=>$usuario
        ]);
    }
    #[Route('/reserva/{id}', name: 'app_reserva')]
    public function reserva(Booking $booking,Request $request,MailerInterface $mailer): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        $bookings = $this->em->getRepository(Booking::class)->obtenerVálidos(new \DateTime());


        //traducciones->
        $reserva_pagina['titulo'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_reserva:titulo','lenguaje'=>$idioma->getId()]);
        if(!isset( $reserva_pagina['titulo']) || empty( $reserva_pagina['titulo']))  $reserva_pagina['titulo'] = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_reserva:titulo']);
        if(!isset( $reserva_pagina['titulo']) || empty( $reserva_pagina['titulo'])){  $reserva_pagina['titulo'] = null;}else{ $reserva_pagina['titulo'] =  $reserva_pagina['titulo']->getValue();}

        $reserva_pagina['tituloForm'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:tituloForm',$idioma);
        if(!isset( $reserva_pagina['tituloForm']) || empty( $reserva_pagina['tituloForm'])){  $reserva_pagina['tituloForm'] = '---';}else{ $reserva_pagina['tituloForm'] =  $reserva_pagina['tituloForm']->getValue();}

        $reserva_pagina['form:MisDatos'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:form:MisDatos',$idioma);
        if(!isset( $reserva_pagina['form:MisDatos']) || empty( $reserva_pagina['form:MisDatos'])){  $reserva_pagina['form:MisDatos'] = '---';}else{ $reserva_pagina['form:MisDatos'] =  $reserva_pagina['form:MisDatos']->getValue();}

        $reserva_pagina['formFecha:titulo'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:formFecha:titulo',$idioma);
        if(!isset( $reserva_pagina['formFecha:titulo']) || empty( $reserva_pagina['formFecha:titulo'])){  $reserva_pagina['formFecha:titulo'] = '---';}else{ $reserva_pagina['formFecha:titulo'] =  $reserva_pagina['formFecha:titulo']->getValue();}

        $reserva_pagina['formFecha:Cantidad'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:formFecha:Cantidad',$idioma);
        if(!isset( $reserva_pagina['formFecha:Cantidad']) || empty( $reserva_pagina['formFecha:Cantidad'])){  $reserva_pagina['formFecha:Cantidad'] = '---';}else{ $reserva_pagina['formFecha:Cantidad'] =  $reserva_pagina['formFecha:Cantidad']->getValue();}

        $reserva_pagina['finalizar'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:finalizar',$idioma);
        if(!isset( $reserva_pagina['finalizar']) || empty( $reserva_pagina['finalizar'])){  $reserva_pagina['finalizar'] = '---';}else{ $reserva_pagina['finalizar'] =  $reserva_pagina['finalizar']->getValue();}

        $reserva_pagina['cancelar'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:cancelar',$idioma);
        if(!isset( $reserva_pagina['cancelar']) || empty( $reserva_pagina['cancelar'])){  $reserva_pagina['cancelar'] = '---';}else{ $reserva_pagina['cancelar'] =  $reserva_pagina['cancelar']->getValue();}

        $reserva_pagina['formTh:Nombre'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:formTh:Nombre',$idioma);
        if(!isset( $reserva_pagina['formTh:Nombre']) || empty( $reserva_pagina['formTh:Nombre'])){  $reserva_pagina['formTh:Nombre'] = '---';}else{ $reserva_pagina['formTh:Nombre'] =  $reserva_pagina['formTh:Nombre']->getValue();}

        $reserva_pagina['formTh:Apellido'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:formTh:Apellido',$idioma);
        if(!isset( $reserva_pagina['formTh:Apellido']) || empty( $reserva_pagina['formTh:Apellido'])){  $reserva_pagina['formTh:Apellido'] = '---';}else{ $reserva_pagina['formTh:Apellido'] =  $reserva_pagina['formTh:Apellido']->getValue();}

        $reserva_pagina['formTh:Accion'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:formTh:Accion',$idioma);
        if(!isset( $reserva_pagina['formTh:Accion']) || empty( $reserva_pagina['formTh:Accion'])){  $reserva_pagina['formTh:Accion'] = '---';}else{ $reserva_pagina['formTh:Accion'] =  $reserva_pagina['formTh:Accion']->getValue();}

        $reserva_pagina['btnagregarpersona'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:btnagregarpersona',$idioma);
        if(!isset( $reserva_pagina['btnagregarpersona']) || empty( $reserva_pagina['btnagregarpersona'])){  $reserva_pagina['btnagregarpersona'] = '---';}else{ $reserva_pagina['btnagregarpersona'] =  $reserva_pagina['btnagregarpersona']->getValue();}

        $reserva_pagina['btnReservar'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:btnReservar',$idioma);
        if(!isset( $reserva_pagina['btnReservar']) || empty( $reserva_pagina['btnReservar'])){  $reserva_pagina['btnReservar'] = '---';}else{ $reserva_pagina['btnReservar'] =  $reserva_pagina['btnReservar']->getValue();}

        $reserva_pagina['form:titulotablaaddicionales'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:form:titulotablaaddicionales',$idioma);
        if(!isset( $reserva_pagina['form:titulotablaaddicionales']) || empty( $reserva_pagina['form:titulotablaaddicionales'])){  $reserva_pagina['form:titulotablaaddicionales'] = '---';}else{ $reserva_pagina['form:titulotablaaddicionales'] =  $reserva_pagina['form:titulotablaaddicionales']->getValue();}

        $reserva_pagina['otrasreservasdisponibles'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:otrasreservasdisponibles',$idioma);
        if(!isset( $reserva_pagina['otrasreservasdisponibles']) || empty( $reserva_pagina['otrasreservasdisponibles'])){  $reserva_pagina['otrasreservasdisponibles'] = '---';}else{ $reserva_pagina['otrasreservasdisponibles'] =  $reserva_pagina['otrasreservasdisponibles']->getValue();}

        $reserva_pagina['TablaReservasAdicionales'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:TablaReservasAdicionales',$idioma);
        if(!isset( $reserva_pagina['TablaReservasAdicionales']) || empty( $reserva_pagina['TablaReservasAdicionales'])){  $reserva_pagina['TablaReservasAdicionales'] = '---';}else{ $reserva_pagina['TablaReservasAdicionales'] =  $reserva_pagina['TablaReservasAdicionales']->getValue();}

        $reserva_pagina['porpersona'] = $this->em->getRepository(TraduccionPlataforma::class)->buscarTraduccion('app_reserva:porpersona',$idioma);
        if(!isset( $reserva_pagina['porpersona']) || empty( $reserva_pagina['porpersona'])){  $reserva_pagina['porpersona'] = '---';}else{ $reserva_pagina['porpersona'] =  $reserva_pagina['porpersona']->getValue();}





        $fechasDatetime = null;
        $datetimeNow = new \DateTime();
        if(!isset($booking) || empty($booking) || !$booking->isHabilitado() || $booking->getValidoHasta() <= ($datetimeNow)){
            return $this->redirectToRoute('app_inicio');
        }
        $fechas = $booking->getFechasdelservicio();

        $formulario = $booking->getFormRequerido();
        if(isset($formulario)&& !empty($formulario)){
            $formulario = json_decode($formulario);
        }

        $fechasDatetime = $booking->getfechasdisponibles();

        $reserva = new SolicitudReserva();
        $reserva->setBooking($booking);
        $reserva->setIdiomaPreferido($idioma);
        $reservaForm = $this->createForm(SolicitudReservaType::class,$reserva);
        $reservaForm->handleRequest($request);
        if($reservaForm->isSubmitted() && $reservaForm->isValid()){
            $fechasarray =json_decode($fechas);
            if(isset($fechasarray) && !empty($fechasarray) && count($fechasarray) >= 1){
                $fecha = '' . ($reservaForm->get('fechaSeleccionadaString')->getData());
                $specific_value = str_replace(' ','T',$fecha);
                $filtro = array_filter($fechasarray, function ($obj) use ($specific_value) {return $obj->fecha == $specific_value;});
                if( count($filtro) >= 1 ){
                    $key = array_key_first($filtro);
                    if($filtro[$key]->cantidad >= (count($reserva->getInChargeOfArray())+1)){
                        $reserva->setFechaSeleccionada(\DateTime::createFromFormat( 'Y-m-d H:i', $fecha));
                    }else{
                        $reservaForm->addError(new FormError('La fecha solicitada no tiene '.(count($reserva->getInChargeOfArray())+1) . ' lugares, seleccione otra fecha.'));
                    }

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


                $emailcontext = [
                    'reserva'=>$reserva,
                    'idiomaPlataforma'=>$idioma,
                    'melink'=>$this->generateUrl('app_status_booking',['tokenId'=>$reserva->getLinkDetalles(),'id'=>$reserva->getId()],UrlGeneratorInterface::ABSOLUTE_URL)
                ];
                $email = (new TemplatedEmail())
                    ->from(new Address('tienda@shophardware.com.ar', $this->em->getRepository(Plataforma::class)->find(1)->getNombre() .' bot'))
                    ->to($reserva->getEmail())
                    ->subject('Reserva - ' . $reserva->getBooking()->getNombre())
                    ->htmlTemplate('email/reservado.html.twig')
                    ->context($emailcontext);
                $mailer->send($email);
                return $this->redirectToRoute('apps_pago',['id'=>$reserva->getId()]);

            }
        }
        return $this->render('reserva/index.html.twig', [
            'controller_name' => 'InicioController',
            'booking'=>$booking,
            'bookings'=>$bookings,
            'reservaForm'=>$reservaForm,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'imagenes'=>json_decode($booking->getImagenes()),
            'fechas'=>$fechasDatetime,
            'formulario'=>$formulario,
            'plataforma'=>$plataforma,
            'traduccion'=>$reserva_pagina
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
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $render = $this->render('inicio/bookings.html.twig',[
            'bookings'=>$bookings,
            'idiomaPlataforma'=>$idioma
            ]);
        return new JsonResponse(['render'=>$render->getContent()],200);
    }


    #[Route('/status/booking/{tokenId}/{id}', defaults:["tokenId"=> '',"id"=>0], name: 'app_status_booking')]
    public function app_status_booking(string $tokenId,SolicitudReserva $solicitudReserva =null , Request $request): Response
    {
        $render=null;
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

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
            'plataforma'=>$plataforma,
            'idiomaPlataforma'=>$idioma,
            'render'=>$render
        ]);
    }
    #[Route('/buscar/status/booking', name: 'app_status_booking_search', methods: ['POST'], options: ['expose'=>true])]
    public function app_status_booking_search(Request $request): Response
    {
        $solicitudes = null;
        $emailSearch = $request->get('emailSearch');
        if(isset($emailSearch) && !empty($emailSearch)){
            $solicitudes = $this->em->getRepository(SolicitudReserva::class)->findBy(['email'=>$emailSearch]);
        }
        $render = $this->render('inicio/status/plantillaBookings.html.twig',[
            'solicitudes'=>$solicitudes
        ]);
        return new JsonResponse(['render'=>$render->getContent()],200);
    }


    #[Route('/consultar/booking', name: 'app_consultar_booking')]
    public function app_consultar_booking( Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);




        return $this->render('inicio/status/searchBooking.html.twig', [
            'controller_name' => 'InicioController',
            'idiomas'=>$idiomas,
            'plataforma'=>$plataforma,
            'idiomaPlataforma'=>$idioma
        ]);
    }



}
