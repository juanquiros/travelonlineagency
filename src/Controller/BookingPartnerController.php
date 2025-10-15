<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\BookingPartner;
use App\Entity\CredencialesMercadoPago;
use App\Entity\Moneda;
use App\Entity\Plataforma;
use App\Entity\Precio;
use App\Entity\SolicitudReserva;
use App\Entity\Usuario;
use App\Form\BookingType;
use App\Services\LanguageService;
use App\Services\MercadoPagoOnboardingService;
use App\Services\notificacion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[IsGranted('ROLE_USER')]
final class BookingPartnerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MercadoPagoOnboardingService $mercadoPagoOnboarding
    ) {
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

        $cuentaMp = $partner->getMercadoPagoCuenta();
        $conectadoMp = $cuentaMp instanceof CredencialesMercadoPago && $cuentaMp->getAccessToken();

        return $this->render('booking_partner/index.html.twig', array_merge($viewData, [
            'partner' => $partner,
            'bookings' => $bookings,
            'navigation' => $this->partnerNavigation('services'),
            'mercadoPagoCuenta' => $cuentaMp,
            'mercadoPagoConectado' => $conectadoMp,
        ]));
    }

    #[Route('/booking-partner/servicio/{id}/reservas', name: 'app_booking_partner_service_reservations', requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_PARTNER')]
    public function serviceReservations(Request $request, Booking $booking): Response
    {
        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner || $booking->getBookingPartner()?->getId() !== $partner->getId()) {
            throw $this->createNotFoundException();
        }

        $fechaFiltro = $request->query->get('ff');

        $repository = $this->entityManager->getRepository(SolicitudReserva::class);

        $reservas = [
            'pendientes' => $repository->reservas($booking->getId(), 1, $fechaFiltro),
            'pagadas' => $repository->reservas($booking->getId(), 2, $fechaFiltro),
            'canceladas' => $repository->reservas($booking->getId(), 3, $fechaFiltro),
        ];

        $personas = [
            'pendientes' => $repository->solicitudesDeBooking($booking->getId(), 1, $fechaFiltro),
            'pagadas' => $repository->solicitudesDeBooking($booking->getId(), 2, $fechaFiltro),
            'canceladas' => $repository->solicitudesDeBooking($booking->getId(), 3, $fechaFiltro),
        ];

        return $this->render('booking_partner/reservations.html.twig', array_merge($this->baseViewData($request), [
            'partner' => $partner,
            'booking' => $booking,
            'fechaFiltro' => $fechaFiltro,
            'reservas' => $reservas,
            'personas' => $personas,
            'navigation' => $this->partnerNavigation('services'),
        ]));
    }

    #[Route('/booking-partner/balance', name: 'app_booking_partner_balance')]
    #[IsGranted('ROLE_PARTNER')]
    public function balance(Request $request): Response
    {
        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner) {
            $this->addFlash('error', 'Tu cuenta no está configurada como partner.');

            return $this->redirectToRoute('app_booking_partner');
        }

        $viewData = $this->baseViewData($request);
        $plataforma = $this->entityManager->getRepository(Plataforma::class)->find(1);
        $credencialesPlataforma = $plataforma?->getCredencialesMercadoPago();
        $credencialesPartner = $partner->getMercadoPagoCuenta();
        $cuenta = null;
        $balance = null;
        $credencialesActualizadas = false;

        if ($credencialesPartner instanceof CredencialesMercadoPago && $credencialesPartner->getAccessToken()) {
            try {
                if ($this->mercadoPagoOnboarding->ensureValidAccessToken($credencialesPartner)) {
                    $credencialesActualizadas = true;
                }

                $cuenta = $this->mercadoPagoOnboarding->syncAccountInformation($credencialesPartner);
                $balance = $this->mercadoPagoOnboarding->fetchBalance($credencialesPartner);
                $credencialesActualizadas = true;
            } catch (\Throwable $exception) {
                $this->addFlash('error', 'No se pudo actualizar tu información de Mercado Pago: ' . $exception->getMessage());
            }
        }

        if ($credencialesActualizadas && $credencialesPartner instanceof CredencialesMercadoPago) {
            $this->entityManager->persist($credencialesPartner);
            $this->entityManager->flush();
        }

        $puedeConectar = $credencialesPlataforma instanceof CredencialesMercadoPago
            && $credencialesPlataforma->getClientId()
            && $credencialesPlataforma->getClientSecret();

        return $this->render('booking_partner/balance.html.twig', array_merge($viewData, [
            'partner' => $partner,
            'navigation' => $this->partnerNavigation('balance'),
            'mercadoPagoCuenta' => $credencialesPartner,
            'mercadoPagoConectado' => $credencialesPartner instanceof CredencialesMercadoPago && $credencialesPartner->getAccessToken(),
            'mercadoPagoPuedeConectar' => $puedeConectar,
            'mercadoPagoBalance' => $balance,
            'mercadoPagoPerfil' => $cuenta,
        ]));
    }

    #[Route('/booking-partner/pagos/conectar', name: 'app_booking_partner_mp_connect')]
    #[IsGranted('ROLE_PARTNER')]
    public function connectMercadoPago(Request $request): Response
    {
        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner) {
            $this->addFlash('error', 'Tu cuenta no está configurada como partner.');

            return $this->redirectToRoute('app_booking_partner');
        }

        $plataforma = $this->entityManager->getRepository(Plataforma::class)->find(1);
        $credencialesPlataforma = $plataforma?->getCredencialesMercadoPago();

        if (!$credencialesPlataforma instanceof CredencialesMercadoPago || !$credencialesPlataforma->getClientId() || !$credencialesPlataforma->getClientSecret()) {
            $this->addFlash('error', 'Contactá al administrador para configurar las credenciales de Mercado Pago.');

            return $this->redirectToRoute('app_booking_partner_balance');
        }

        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('mp_partner_state', $state);

        try {
            $authorizationUrl = $this->mercadoPagoOnboarding->createAuthorizationUrl(
                $credencialesPlataforma,
                $this->generateUrl('app_booking_partner_mp_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
                $state,
                ['offline_access', 'read', 'write']
            );
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'No se pudo generar el enlace de Mercado Pago: ' . $exception->getMessage());

            return $this->redirectToRoute('app_booking_partner_balance');
        }

        return $this->redirect($authorizationUrl);
    }

    #[Route('/booking-partner/pagos/callback', name: 'app_booking_partner_mp_callback')]
    #[IsGranted('ROLE_PARTNER')]
    public function callbackMercadoPago(Request $request): Response
    {
        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner) {
            $this->addFlash('error', 'Tu cuenta no está configurada como partner.');

            return $this->redirectToRoute('app_booking_partner');
        }

        $session = $request->getSession();
        $expectedState = $session->get('mp_partner_state');
        $session->remove('mp_partner_state');
        $state = $request->query->get('state');

        if (!$state || !$expectedState || $state !== $expectedState) {
            $this->addFlash('error', 'El proceso de autorización caducó o no es válido.');

            return $this->redirectToRoute('app_booking_partner_balance');
        }

        if ($request->query->has('error')) {
            $this->addFlash('error', 'Mercado Pago rechazó la vinculación: ' . $request->query->get('error_description', ''));

            return $this->redirectToRoute('app_booking_partner_balance');
        }

        $authorizationCode = $request->query->get('code');
        if (!$authorizationCode) {
            $this->addFlash('error', 'Mercado Pago no devolvió el código de autorización.');

            return $this->redirectToRoute('app_booking_partner_balance');
        }

        $plataforma = $this->entityManager->getRepository(Plataforma::class)->find(1);
        $credencialesPlataforma = $plataforma?->getCredencialesMercadoPago();

        if (!$credencialesPlataforma instanceof CredencialesMercadoPago || !$credencialesPlataforma->getClientId() || !$credencialesPlataforma->getClientSecret()) {
            $this->addFlash('error', 'La plataforma no tiene credenciales configuradas.');

            return $this->redirectToRoute('app_booking_partner_balance');
        }

        $credencialesPartner = $partner->getMercadoPagoCuenta() ?? new CredencialesMercadoPago();
        $credencialesPartner->setClientId($credencialesPlataforma->getClientId());
        $credencialesPartner->setClientSecret($credencialesPlataforma->getClientSecret());

        try {
            $this->mercadoPagoOnboarding->exchangeAuthorizationCode(
                $credencialesPartner,
                (string) $authorizationCode,
                $this->generateUrl('app_booking_partner_mp_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            $this->mercadoPagoOnboarding->syncAccountInformation($credencialesPartner);

            $partner->setMercadoPagoCuenta($credencialesPartner);
            $this->entityManager->persist($credencialesPartner);
            $this->entityManager->persist($partner);
            $this->entityManager->flush();

            $this->addFlash('success', 'Conectaste tu cuenta de Mercado Pago correctamente.');
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'No se pudo vincular Mercado Pago: ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_booking_partner_balance');
    }

    #[Route('/booking-partner/pagos/desconectar', name: 'app_booking_partner_mp_disconnect', methods: ['POST'])]
    #[IsGranted('ROLE_PARTNER')]
    public function disconnectMercadoPago(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('partner_mp_disconnect', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner) {
            return $this->redirectToRoute('app_booking_partner');
        }

        $credenciales = $partner->getMercadoPagoCuenta();
        if ($credenciales instanceof CredencialesMercadoPago) {
            $partner->setMercadoPagoCuenta(null);
            $this->entityManager->persist($partner);
            $this->entityManager->remove($credenciales);
            $this->entityManager->flush();

            $this->addFlash('success', 'Desconectaste Mercado Pago de tu cuenta.');
        }

        return $this->redirectToRoute('app_booking_partner_balance');
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
        $currencies = $this->entityManager->getRepository(Moneda::class)->findBy(['habilitada' => true]);
        $currencyPayload = array_map(
            static fn (Moneda $moneda) => [
                'id' => $moneda->getId(),
                'nombre' => $moneda->getNombre(),
                'simbolo' => $moneda->getSimbolo(),
            ],
            $currencies
        );

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $booking->setBookingPartner($partner);

            $this->normaliseBookingDates($booking);
            $this->syncBookingPrices($booking, $form->get('preciosaux')->getData());

            $this->entityManager->persist($booking);
            $this->entityManager->flush();

            $this->addFlash('success', $isNew ? 'Servicio creado correctamente.' : 'Servicio actualizado correctamente.');

            return $this->redirectToRoute('app_booking_partner');
        }

        return $this->render('booking_partner/service_form.html.twig', array_merge($this->baseViewData($request), [
            'form' => $form->createView(),
            'isNew' => $isNew,
            'booking' => $booking,
            'monedas' => $currencyPayload,
            'imagenes' => $this->decodeJsonField($booking->getImagenes()),
            'datosRequeridos' => $this->decodeJsonField($booking->getFormRequerido()),
            'fechas' => $this->decodeJsonField($booking->getFechasdelservicio()),
            'precios' => $this->serialiseBookingPrices($booking),
            'navigation' => $this->partnerNavigation('services'),
        ]));
    }

    #[Route('/booking-partner/servicio/imagen', name: 'app_booking_partner_service_image_upload', methods: ['POST'])]
    #[IsGranted('ROLE_PARTNER')]
    public function uploadServiceImage(Request $request, SluggerInterface $slugger): Response
    {
        $partner = $this->getCurrentPartner();

        if (!$partner instanceof BookingPartner) {
            throw $this->createNotFoundException();
        }

        $payload = json_decode((string) $request->request->get('data'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $bookingId = $payload['bookingid'] ?? null;
        $isCover = !empty($payload['isportada']);
        $existingFromForm = $payload['enform'] ?? null;

        $booking = null;
        if (!empty($bookingId)) {
            $booking = $this->entityManager->getRepository(Booking::class)->find($bookingId);

            if (!$booking instanceof Booking || $booking->getBookingPartner()?->getId() !== $partner->getId()) {
                throw $this->createNotFoundException();
            }
        }

        $images = [];
        if (!empty($existingFromForm)) {
            $decoded = json_decode($existingFromForm, true);
            $images = is_array($decoded) ? $decoded : [];
        } elseif ($booking instanceof Booking) {
            $images = $this->decodeJsonField($booking->getImagenes());
        }

        $uploadedFiles = $request->files->get('imagen');
        if (!$uploadedFiles instanceof UploadedFile && !is_array($uploadedFiles)) {
            return new JsonResponse(['files' => $images], Response::HTTP_OK);
        }

        $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $upload = $this->uploadImageFile($file, $slugger);
            if (!$upload['upload']) {
                continue;
            }

            if ($isCover) {
                foreach ($images as &$image) {
                    $image['portada'] = false;
                }
                unset($image);
            }

            $images[] = [
                'imagen' => $upload['filename'],
                'portada' => $isCover || empty($images),
            ];

            $isCover = false; // solo la primera imagen subida puede forzar portada
        }

        return new JsonResponse(['files' => $images], Response::HTTP_OK);
    }

    private function normaliseBookingDates(Booking $booking): void
    {
        $raw = $booking->getFechasdelservicio();
        if ($raw === null || $raw === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || $decoded === []) {
            $booking->setFechasdelservicio(null);
            return;
        }

        $normalised = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry) || empty($entry['fecha'])) {
                continue;
            }

            $key = (string) $entry['fecha'];
            $cantidad = isset($entry['cantidad']) ? (int) $entry['cantidad'] : 0;

            if (!array_key_exists($key, $normalised)) {
                $normalised[$key] = [
                    'fecha' => $key,
                    'cantidad' => max(0, $cantidad),
                ];
                continue;
            }

            $normalised[$key]['cantidad'] += $cantidad;
            if ($normalised[$key]['cantidad'] < 0) {
                $normalised[$key]['cantidad'] = 0;
            }
        }

        $booking->setFechasdelservicio(json_encode(array_values($normalised)) ?: null);
    }

    private function syncBookingPrices(Booking $booking, ?string $payload): void
    {
        $existing = [];
        foreach ($booking->getPrecios() as $precio) {
            if ($precio->getId() !== null) {
                $existing[$precio->getId()] = $precio;
            }
        }

        $persistedIds = [];
        if (!empty($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (!is_array($entry) || empty($entry['monedaId'])) {
                        continue;
                    }

                    $moneda = $this->entityManager->getRepository(Moneda::class)->find((int) $entry['monedaId']);
                    if (!$moneda instanceof Moneda) {
                        continue;
                    }

                    $precioEntity = null;
                    $entryId = $entry['id'] ?? null;
                    if ($entryId && isset($existing[(int) $entryId])) {
                        $precioEntity = $existing[(int) $entryId];
                    }

                    if (!$precioEntity instanceof Precio) {
                        $precioEntity = new Precio();
                        $booking->addPrecio($precioEntity);
                    }

                    $precioEntity->setMoneda($moneda);
                    $precioEntity->setValor(isset($entry['valor']) ? (float) $entry['valor'] : 0.0);
                    $this->entityManager->persist($precioEntity);

                    if ($precioEntity->getId() !== null) {
                        $persistedIds[] = $precioEntity->getId();
                    }
                }
            }
        }

        foreach ($booking->getPrecios() as $precio) {
            $id = $precio->getId();
            if ($id !== null && !in_array($id, $persistedIds, true)) {
                $booking->removePrecio($precio);
                $this->entityManager->remove($precio);
            }
        }
    }

    private function decodeJsonField(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function serialiseBookingPrices(Booking $booking): array
    {
        $prices = [];
        foreach ($booking->getPrecios() as $precio) {
            $prices[] = [
                'id' => $precio->getId(),
                'valor' => $precio->getValor(),
                'monedaId' => $precio->getMoneda()?->getId(),
                'monedaNombre' => $precio->getMoneda()?->getNombre(),
                'monedaSimbolo' => $precio->getMoneda()?->getSimbolo(),
            ];
        }

        return $prices;
    }

    private function uploadImageFile(UploadedFile $file, SluggerInterface $slugger): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $file->guessExtension();

        $mime = $file->getMimeType();
        $quality = $file->getSize() > 1536 ? 70 : 100;

        try {
            if ($mime === 'image/png' || $mime === 'image/ico' || $mime === 'image/x-icon' || $mime === 'image/vnd.microsoft.icon') {
                $file->move($this->getParameter('img_booking'), $newFilename);
            } else {
                $resource = match ($mime) {
                    'image/jpeg' => imagecreatefromjpeg($file->getPathname()),
                    'image/gif' => imagecreatefromgif($file->getPathname()),
                    default => imagecreatefromjpeg($file->getPathname()),
                };

                imagejpeg($resource, $this->getParameter('img_booking') . '/' . $newFilename, $quality);
            }
        } catch (FileException $exception) {
            return ['filename' => '', 'upload' => false];
        }

        return ['filename' => $newFilename, 'upload' => true];
    }

    private function partnerNavigation(string $active): array
    {
        return [
            'services' => $active === 'services',
            'balance' => $active === 'balance',
        ];
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
            'vapidPublicKey' => notificacion::getPublicKey(),
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
