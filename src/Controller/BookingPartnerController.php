<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\BookingPartner;
use App\Entity\Plataforma;
use App\Entity\Usuario;
use App\Form\BookingType;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class BookingPartnerController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/booking-partner', name: 'app_booking_partner')]
    public function index(Request $request): Response
    {
        $user = $this->getAuthenticatedUser();
        $partner = $user->getBPartner();
        $viewData = $this->baseViewData($request);

        if (!$partner instanceof BookingPartner) {
            return $this->render('booking_partner/pending.html.twig', array_merge($viewData, [
                'reason' => 'missing',
            ]));
        }

        if (!$partner->isHabilitado()) {
            return $this->render('booking_partner/pending.html.twig', array_merge($viewData, [
                'reason' => 'pending',
            ]));
        }

        if (!$this->isGranted('ROLE_PARTNER')) {
            return $this->render('booking_partner/pending.html.twig', array_merge($viewData, [
                'reason' => 'role-missing',
            ]));
        }

        $bookings = $this->entityManager->getRepository(Booking::class)->findBy([
            'bookingPartner' => $partner,
        ]);

        return $this->render('booking_partner/index.html.twig', array_merge($viewData, [
            'partner' => $partner,
            'bookings' => $bookings,
        ]));
    }

    #[Route('/booking-partner/servicio/nuevo', name: 'app_booking_partner_service_new')]
    #[IsGranted('ROLE_PARTNER')]
    public function newService(Request $request): Response
    {
        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner) {
            $this->addFlash('error', 'Tu cuenta no está configurada como partner.');
            return $this->redirectToRoute('app_booking_partner');
        }

        if (!$partner->isHabilitado()) {
            $this->addFlash('error', 'Tu cuenta aún no está habilitada para crear servicios.');
            return $this->redirectToRoute('app_booking_partner');
        }

        $booking = new Booking();
        $booking->setBookingPartner($partner);

        $plataforma = $this->entityManager->getRepository(Plataforma::class)->find(1);
        if ($plataforma instanceof Plataforma && $plataforma->getLanguageDef()) {
            $booking->setLenguaje($plataforma->getLanguageDef());
        }

        $booking->setValidoHasta(new \DateTimeImmutable());

        return $this->handleServiceForm($request, $booking, $partner, true);
    }

    #[Route('/booking-partner/servicio/{id}', name: 'app_booking_partner_service_edit', requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_PARTNER')]
    public function editService(Request $request, Booking $booking): Response
    {
        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner) {
            $this->addFlash('error', 'Tu cuenta no está configurada como partner.');
            return $this->redirectToRoute('app_booking_partner');
        }

        if (!$partner->isHabilitado()) {
            $this->addFlash('error', 'Tu cuenta aún no está habilitada para editar servicios.');
            return $this->redirectToRoute('app_booking_partner');
        }

        if ($booking->getBookingPartner()?->getId() !== $partner->getId()) {
            throw $this->createNotFoundException();
        }

        return $this->handleServiceForm($request, $booking, $partner, false);
    }

    private function handleServiceForm(Request $request, Booking $booking, BookingPartner $partner, bool $isNew): Response
    {
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->setBookingPartner($partner);
            $this->entityManager->persist($booking);
            $this->entityManager->flush();

            $this->addFlash('success', $isNew ? 'Servicio creado correctamente.' : 'Servicio actualizado correctamente.');

            return $this->redirectToRoute('app_booking_partner');
        }

        return $this->render('booking_partner/service_form.html.twig', array_merge($this->baseViewData($request), [
            'form' => $form->createView(),
            'isNew' => $isNew,
            'booking' => $booking,
        ]));
    }

    private function baseViewData(Request $request): array
    {
        $plataforma = $this->entityManager->getRepository(Plataforma::class)->find(1);
        $idiomas = LanguageService::getLenguajes($this->entityManager);
        $idioma = LanguageService::getLenguaje($this->entityManager, $request);

        return [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
        ];
    }

    private function getCurrentPartner(): ?BookingPartner
    {
        return $this->getAuthenticatedUser()->getBPartner();
    }

    private function getAuthenticatedUser(): Usuario
    {
        $user = $this->getUser();

        if (!$user instanceof Usuario) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
