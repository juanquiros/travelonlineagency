<?php

namespace App\Controller;

use App\Entity\Precio;
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
    #[Route('/pago/booking/{id}', name: 'apps_pago')]
    public function index(SolicitudReserva $solicitudReserva, Request $request): Response
    {
        $mp = true;
        $pp = true;
        if(!isset($solicitudReserva) || empty($solicitudReserva)) return $this->redirectToRoute('app_inicio');
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $precioBokingMp=$this->em->getRepository(Precio::class)->findOneBy(['moneda'=>2,'booking'=>$solicitudReserva->getBooking()->getId()]);
        $precioBokingPP=$this->em->getRepository(Precio::class)->findOneBy(['moneda'=>1,'booking'=>$solicitudReserva->getBooking()->getId()]);
        $adicionales = json_decode($solicitudReserva->getInChargeOf());
        $cantidad = count($adicionales ) + 1;
        return $this->render('pago/index.html.twig', [
            'controller_name' => 'PagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'PayPalLink'=>$this->generateUrl('paypal_pay_booking',['id'=>$solicitudReserva->getId()]),
            'MercadoPagoLink'=>$this->generateUrl('mercadopago_pay_booking',['id'=>$solicitudReserva->getId()]),
            'habilitado'=>['mp'=>$precioBokingMp,'pp'=>$precioBokingPP],
            'cantidad'=>$cantidad
        ]);
    }
}
