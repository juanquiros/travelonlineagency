<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MercadoPagoController extends AbstractController
{
    #[Route('/mercado/pago', name: 'app_mercado_pago')]
    public function index(): Response
    {
        return $this->render('mercado_pago/index.html.twig', [
            'controller_name' => 'MercadoPagoController',
        ]);
    }
}
