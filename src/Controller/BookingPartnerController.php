<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BookingPartnerController extends AbstractController
{
    #[Route('/booking-partner', name: 'app_booking_partner')]
    public function index(): Response
    {
        return $this->render('booking_partner/index.html.twig', [
            'controller_name' => 'BookingPartnerController',
        ]);
    }
}
