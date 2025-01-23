<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PagoController extends AbstractController
{
    #[Route('/pago', name: 'app_pago')]
    public function index(): Response
    {
        return $this->render('pago/index.html.twig', [
            'controller_name' => 'PagoController',
        ]);
    }
}
