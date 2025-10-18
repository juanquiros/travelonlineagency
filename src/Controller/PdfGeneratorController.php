<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\EstadoReserva;
use App\Entity\SolicitudReserva;
use App\Entity\TransferRequest;
use App\Entity\Usuario;
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

    #[Route('/administrador/pdf/generator/booking/{id}/{estadoId}', name: 'app_pdf_generator')]
    public function index(Booking $booking, Request $request, Pdf $pdf, int $estadoId): Response
    {
        $this->assertPdfAccess($booking);

        return $this->buildPdfResponse($booking, $estadoId, $request, $pdf);
    }

    #[Route('/booking-partner/pdf/generator/booking/{id}/{estadoId}', name: 'app_booking_partner_pdf')]
    public function partnerPdf(Booking $booking, Request $request, Pdf $pdf, int $estadoId): Response
    {
        $this->assertPdfAccess($booking, true);

        return $this->buildPdfResponse($booking, $estadoId, $request, $pdf);
    }

    #[Route('/transfer/pdf/{token}', name: 'app_transfer_pdf')]
    public function transferPdf(string $token, Request $request, Pdf $pdf): Response
    {
        $solicitud = $this->em->getRepository(TransferRequest::class)->findOneBy(['tokenSeguimiento' => $token]);

        if (!$solicitud instanceof TransferRequest) {
            throw $this->createNotFoundException();
        }

        $trackingUrl = $this->generateUrl('app_transfer_tracking', ['token' => $token], true);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        $html = $this->renderView('pdf_generator/transfer/solicitud.html.twig', [
            'solicitud' => $solicitud,
            'trackingUrl' => $trackingUrl,
            'plataforma' => $plataforma,
            'generadoEn' => new \DateTimeImmutable(),
        ]);

        $pdf->setOption('enable-local-file-access', true);

        return new PdfResponse(
            $pdf->getOutputFromHtml($html, [
                'page-size' => 'A4',
                'orientation' => 'Portrait',
                'encoding' => 'utf-8',
                'margin-top' => 10,
                'margin-bottom' => 15,
            ]),
            sprintf('traslado-%s.pdf', $solicitud->getTokenSeguimiento())
        );
    }

    private function buildPdfResponse(Booking $booking, int $estadoId, Request $request, Pdf $pdf): Response
    {
        $contenido = $request->query->all();
        $fechafiltro = null;
        if (isset($contenido) && isset($contenido['ff']) && !empty($contenido['ff'])) {
            $fechafiltro = $contenido['ff'];
        }

        $reservas = $this->em->getRepository(SolicitudReserva::class)->reservas($booking->getId(), $estadoId, $fechafiltro);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $solicitudesDelBooking = $this->em->getRepository(SolicitudReserva::class)->solicitudesDeBooking($booking->getId(), $estadoId, $fechafiltro);
        $estado = $this->em->getRepository(EstadoReserva::class)->find($estadoId);
        $arrContextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];
        $html = $this->renderView('pdf_generator/administrador/detallesBooking.html.twig', [
            'idiomaPlataforma' => $idioma,
            'booking' => $booking,
            'reservas' => $reservas,
            'totalAprobado' => $solicitudesDelBooking,
            'estado' => $estado,
            'ico' => 'data:image/png;base64, ' . base64_encode(file_get_contents($this->generateUrl('app_inicio', [], false) . 'img/logosinfondo.png', false, stream_context_create($arrContextOptions))),
        ]);
        $pdf->setOption('enable-local-file-access', true);
        $filename = 'Listado de reservas: ' . $booking->getNombre() . '-' . (new \DateTime())->format('dmYHi') . '.pdf';

        return new PdfResponse(
            $pdf->getOutputFromHtml(
                $html,
                [
                    'lowquality' => false,
                    'disable-javascript' => true,
                    'page-size' => 'A4',
                    'images' => true,
                    'header-left' => utf8_decode('Listado de reservas - ' . $booking->getNombre()),
                    'footer-font-size' => '8',
                    'footer-right' => utf8_decode('Usuario:' . $this->getUser()->getNombre() . ' - generado el ' . date('\ d.m.Y\ H:i') . ' - página [page] de [topage]'),
                    'footer-left' => utf8_decode($this->generateUrl('app_inicio', [], false)),
                    'print-media-type' => true,
                    'encoding' => 'utf-8',
                    'outline-depth' => 8,
                    'orientation' => 'Portrait',
                ]
            ),
            $filename
        );
    }

    private function assertPdfAccess(Booking $booking, bool $mustBePartnerOwner = false): void
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') && !$mustBePartnerOwner) {
            return;
        }

        if (!$user instanceof Usuario) {
            throw $this->createAccessDeniedException();
        }

        $partner = $user->getBPartner();

        if (!$partner || $booking->getBookingPartner()?->getId() !== $partner->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

}
