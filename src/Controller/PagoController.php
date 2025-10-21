<?php

namespace App\Controller;

use App\Entity\Plataforma;
use App\Entity\SolicitudReserva;
use App\Services\LanguageService;
use App\Services\PaymentOptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PagoController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PaymentOptionsResolver $paymentOptions
    ) {
    }
    #[Route('/pago/booking/{id}', name: 'apps_pago')]
    public function index(SolicitudReserva $solicitudReserva, Request $request): Response
    {
        if(!isset($solicitudReserva) || empty($solicitudReserva) || $solicitudReserva->getEstado()->getId() == 2 ) return $this->redirectToRoute('app_inicio');
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $cantidad = $solicitudReserva->getPassengerCount();
        $opciones = $plataforma instanceof Plataforma ? $this->paymentOptions->getBookingOptions($solicitudReserva, $plataforma) : [];
        return $this->render('pago/index.html.twig', [
            'controller_name' => 'PagoController',
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'plataforma'=>$plataforma,
            'solicitud'=>$solicitudReserva,
            'opcionesPago' => array_map(fn(array $opcion) => array_merge($opcion, [
                'url' => $this->generateUrl($opcion['route'], $opcion['params'] ?? []),
            ]), $opciones),
            'cantidad' => $cantidad,
            'usuario' => $this->getUser(),
        ]);
    }
}
