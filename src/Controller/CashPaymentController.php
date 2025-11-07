<?php

namespace App\Controller;

use App\Entity\CashPayment;
use App\Entity\Plataforma;
use App\Entity\SolicitudReserva;
use App\Entity\TransferRequest;
use App\Repository\CashPaymentRepository;
use App\Services\LanguageService;
use App\Services\PaymentOptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CashPaymentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CashPaymentRepository $cashPayments,
        private readonly PaymentOptionsResolver $optionsResolver
    ) {
    }

    #[Route('/pay/cash/booking/{id}', name: 'cash_pay_booking', methods: ['GET'])]
    public function booking(SolicitudReserva $reserva, Request $request): Response
    {
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        if (!$plataforma instanceof Plataforma) {
            throw $this->createNotFoundException('No se encontró la configuración de la plataforma.');
        }

        if (!$plataforma->isEnableCashPayments()) {
            $this->addFlash('error', 'El pago en efectivo no está disponible actualmente.');

            return $this->redirectToRoute('apps_pago', ['id' => $reserva->getId()]);
        }

        if ($reserva->getEstado()?->getId() === 2) {
            $this->addFlash('info', 'La reserva ya se encuentra confirmada.');

            return $this->redirectToRoute('app_status_booking', [
                'tokenId' => $reserva->getLinkDetalles(),
                'id' => $reserva->getId(),
            ]);
        }

        if ($reserva->getEstado()?->getId() === 3) {
            $this->addFlash('error', 'La reserva fue cancelada y no admite nuevos pagos.');

            return $this->redirectToRoute('apps_pago', ['id' => $reserva->getId()]);
        }

        $option = $this->findOption('cash', $this->optionsResolver->getBookingOptions($reserva, $plataforma));
        if (!$option) {
            $this->addFlash('error', 'No se pudo calcular el monto para el pago en efectivo.');

            return $this->redirectToRoute('apps_pago', ['id' => $reserva->getId()]);
        }

        $cashPayment = $this->cashPayments->findLatestForReservation($reserva) ?? new CashPayment();
        $cashPayment->setSolicitudReserva($reserva);
        $cashPayment->setReference(sprintf('booking-%d', $reserva->getId()));
        $cashPayment->setAmount($option['total']);
        $cashPayment->setCurrency($option['currency']);
        $cashPayment->setStatus(CashPayment::STATUS_PENDING);
        $cashPayment->setNotes($plataforma->getCashPaymentInstructions());

        $this->em->persist($cashPayment);
        $this->em->flush();

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);

        return $this->render('pago/cash.html.twig', [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
            'option' => $option,
            'cashPayment' => $cashPayment,
            'context' => 'booking',
            'reserva' => $reserva,
            'returnUrl' => $this->generateUrl('app_status_booking', [
                'tokenId' => $reserva->getLinkDetalles(),
                'id' => $reserva->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    #[Route('/pay/cash/transfer/{id}', name: 'cash_pay_transfer', methods: ['GET'])]
    public function transfer(TransferRequest $solicitud, Request $request): Response
    {
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        if (!$plataforma instanceof Plataforma) {
            throw $this->createNotFoundException('No se encontró la configuración de la plataforma.');
        }

        if (!$plataforma->isEnableCashPayments()) {
            $this->addFlash('error', 'El pago en efectivo no está disponible para traslados.');

            return $this->redirectToRoute('app_transfer_summary', ['token' => $solicitud->getTokenSeguimiento()]);
        }

        if ($solicitud->getEstado() === TransferRequest::ESTADO_CANCELADO) {
            $this->addFlash('error', 'El traslado fue cancelado y no puede abonarse.');

            return $this->redirectToRoute('app_transfer_summary', ['token' => $solicitud->getTokenSeguimiento()]);
        }

        $option = $this->findOption('cash', $this->optionsResolver->getTransferOptions($solicitud, $plataforma));
        if (!$option) {
            $this->addFlash('error', 'No se encontró un monto válido para el pago en efectivo.');

            return $this->redirectToRoute('app_transfer_summary', ['token' => $solicitud->getTokenSeguimiento()]);
        }

        $cashPayment = $this->cashPayments->findLatestForTransfer($solicitud) ?? new CashPayment();
        $cashPayment->setTransferRequest($solicitud);
        $cashPayment->setReference(sprintf('transfer-%d', $solicitud->getId()));
        $cashPayment->setAmount($option['total']);
        $cashPayment->setCurrency($option['currency']);
        $cashPayment->setStatus(CashPayment::STATUS_PENDING);
        $cashPayment->setNotes($plataforma->getCashPaymentInstructions());

        $this->em->persist($cashPayment);
        $this->em->flush();

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);

        return $this->render('pago/cash.html.twig', [
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $this->getUser(),
            'option' => $option,
            'cashPayment' => $cashPayment,
            'context' => 'transfer',
            'transfer' => $solicitud,
            'returnUrl' => $this->generateUrl('app_transfer_tracking', [
                'token' => $solicitud->getTokenSeguimiento(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $options
     */
    private function findOption(string $type, array $options): ?array
    {
        foreach ($options as $option) {
            if (($option['type'] ?? null) === $type && ($option['available'] ?? true)) {
                return $option;
            }
        }

        return null;
    }
}
