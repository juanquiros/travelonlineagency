<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\SolicitudReserva;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;

use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/administrador/pdf/generator/booking/{id}', name: 'app_pdf_generator')]
    public function index(Booking $booking, Request $request,Pdf $pdf): Response
    {
        $reservas = $this->em->getRepository(SolicitudReserva::class)->findBy(['estado'=>2,'Booking'=>$booking->getId()]);
        $idioma = LanguageService::getLenguaje($this->em,$request);
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
            'title'=>'Test',
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
                    'header-left'=>utf8_decode('Listado de reservas'),
                    'footer-font-size'=>'8',
                    'footer-right'=>utf8_decode('página [page] de [topage] - generado el '.date('\ d.m.Y\ H:i')),
                    'footer-left'=>utf8_decode('https://www.shophardware.com.ar'),
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
