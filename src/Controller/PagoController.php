<?php

namespace App\Controller;

use App\Entity\SolicitudReserva;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PagoController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    #[Route('/pago/{id}', name: 'apps_pago')]
    public function index(SolicitudReserva $solicitudReserva, Request $request): Response
    {
        if(!isset($solicitudReserva) || empty($solicitudReserva)) return $this->redirectToRoute('app_inicio');
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);


        return $this->render('pago/index.html.twig', [
            'controller_name' => 'PagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'PayPalLink'=>$this->generateUrl('paypal_pay_booking',['id'=>$solicitudReserva->getId()]),
            'MercadoPagoLink'=>$this->generateUrl('mercadopago_pay_booking',['id'=>$solicitudReserva->getId()])
        ]);
    }
}
