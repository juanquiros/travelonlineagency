<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\EstadoReserva;
use App\Entity\SolicitudReserva;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;

use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PdfGeneratorController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/administrador/pdf/generator/booking/{id}/{estadoId}', name: 'app_pdf_generator')]
    public function index(Booking $booking,int $estadoId = null, Request $request,Pdf $pdf): Response
    {
        if(!isset($booking) || empty($booking) || !isset($estadoId) || empty($estadoId)) return new JsonResponse(['status'=>'fail'],200);
        $reservas = $this->em->getRepository(SolicitudReserva::class)->findBy(['estado'=>$estadoId,'Booking'=>$booking->getId()]);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $solicitudesDelBooking = $this->em->getRepository(SolicitudReserva::class)->solicitudesDeBooking($booking->getId(),$estadoId);
        $estado = $this->em->getRepository(EstadoReserva::class)->find($estadoId);
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $html = $this->renderView('pdf_generator/administrador/detallesBooking.html.twig',[
            'idiomaPlataforma'=>$idioma,
            'booking'=>$booking,
            'reservas'=>$reservas,
            'totalAprobado'=>$solicitudesDelBooking,
            'estado'=>$estado,
            'ico'=>'data:image/png;base64, '. base64_encode(file_get_contents($this->generateUrl('app_inicio',[],false).'img/logosinfondo.png' ,false, stream_context_create($arrContextOptions)))
        ]);
        $pdf->setOption('enable-local-file-access', true);
        $filename = 'Listado de reservas: '. $booking->getNombre().'-'.(new \DateTime())->format('dmYHi').'.pdf';
        return new PdfResponse(
            $pdf->getOutputFromHtml($html,
                ['lowquality' => false,
                    'disable-javascript'=>true,
                    'page-size'=>'A4',
                    'images' => true,
                    'header-left'=>utf8_decode('Listado de reservas - '.$booking->getNombre()),
                    'footer-font-size'=>'8',
                    'footer-right'=>utf8_decode('Usuario:'.$this->getUser()->getNombre().' - generado el '.date('\ d.m.Y\ H:i').' - página [page] de [topage]'),
                    'footer-left'=>utf8_decode($this->generateUrl('app_inicio',[],false)),
                    'print-media-type' => true,
                    'encoding' => 'utf-8',
                    'outline-depth' => 8,
                    'orientation' => 'Portrait']
            ),
            $filename
        );/*
        return $this->render('pdf_generator/administrador/detallesBooking.html.twig',[
            'idiomaPlataforma'=>$idioma,
            'booking'=>$booking,
            'reservas'=>$reservas,
            'title'=>'Test'
        ]);*/
    }

}
