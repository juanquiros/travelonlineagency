<?php

namespace App\Controller;

use App\Entity\CashPayment;
use App\Entity\DriverProfile;
use App\Entity\DriverWithdrawalRequest;
use App\Entity\Plataforma;
use App\Entity\TransferAssignment;
use App\Entity\TransferRequest;
use App\Entity\Usuario;
use App\Repository\CashPaymentRepository;
use App\Repository\DriverBalanceEntryRepository;
use App\Repository\DriverWithdrawalRequestRepository;
use App\Entity\VehicleType;
use App\Repository\TransferAssignmentRepository;
use App\Repository\TransferRequestRepository;
use App\Repository\VehicleFeatureRepository;
use App\Repository\VehicleTypeRepository;
use App\Services\VehicleFeatureManager;
use App\Services\DriverBalanceService;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chofer')]
#[IsGranted('ROLE_DRIVER')]
final class DriverController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'app_driver_dashboard', methods: ['GET'])]
    public function dashboard(
        Request $request,
        TransferAssignmentRepository $assignmentRepository,
        TransferRequestRepository $requestRepository,
        VehicleTypeRepository $vehicleTypeRepository,
        VehicleFeatureRepository $vehicleFeatureRepository,
    ): Response
    {
        $usuario = $this->requireAuthenticatedUser();
        $perfil = $this->em->getRepository(DriverProfile::class)->findOneBy(['usuario' => $usuario]);

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $vehicleTypes = $vehicleTypeRepository->findActiveOrdered();
        $vehicleFeatures = $vehicleFeatureRepository->findActiveOrdered();

        $asignaciones = [];
        $disponibles = [];

        if ($perfil instanceof DriverProfile && $perfil->isAprobado()) {
            $asignaciones = $assignmentRepository->findActivosParaChofer($usuario);

            $pendientes = $requestRepository->findPendientes();
            foreach ($pendientes as $solicitud) {
                if ($assignmentRepository->contarActivasPorSolicitud($solicitud) === 0) {
                    $disponibles[] = $solicitud;
                }
            }
        }

        return $this->render('driver/index.html.twig', [
            'usuario' => $usuario,
            'perfil' => $perfil,
            'asignaciones' => $asignaciones,
            'disponibles' => $disponibles,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'plataforma' => $plataforma,
            'vehicleTypes' => $vehicleTypes,
            'vehicleFeatures' => $vehicleFeatures,
        ]);
    }

    #[Route('/vehiculo', name: 'app_driver_vehicle_update', methods: ['POST'])]
    public function updateVehicle(Request $request, VehicleFeatureManager $vehicleFeatureManager): RedirectResponse
    {
        $usuario = $this->requireAuthenticatedUser();
        $perfil = $this->requireApprovedProfile($usuario);

        if (!$this->isCsrfTokenValid('driver_vehicle_' . $perfil->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        $vehicleTypeId = (int) $request->request->get('vehicle_type', 0);
        $vehicleType = null;
        if ($vehicleTypeId > 0) {
            $vehicleType = $this->em->getRepository(VehicleType::class)->find($vehicleTypeId);
        }

        if ($vehicleType instanceof VehicleType && $vehicleType->isActivo()) {
            $perfil->setVehicleType($vehicleType);
        } elseif ($vehicleTypeId === 0) {
            $perfil->setTipoVehiculo('');
            $perfil->setVehicleType(null);
        } else {
            $this->addFlash('error', 'Seleccioná un tipo de vehículo válido.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        $selectedFeatures = $request->request->all('features');
        $newFeatures = $request->request->get('new_features');

        $vehicleFeatureManager->syncDriverFeatures(
            $perfil,
            is_array($selectedFeatures) ? $selectedFeatures : [],
            is_string($newFeatures) ? $newFeatures : null
        );

        $this->em->persist($perfil);
        $this->em->flush();

        $this->addFlash('success', 'Actualizaste la información de tu vehículo.');

        return $this->redirectToRoute('app_driver_dashboard');
    }

    #[Route('/solicitud/{id}/capturar', name: 'app_driver_capture', methods: ['POST'])]
    public function capture(Request $request, TransferRequest $solicitud, TransferAssignmentRepository $assignmentRepository): RedirectResponse
    {
        $usuario = $this->requireAuthenticatedUser();
        $perfil = $this->requireApprovedProfile($usuario);

        if (!$this->isCsrfTokenValid('capture_transfer_' . $solicitud->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        if ($solicitud->getEstado() !== TransferRequest::ESTADO_PENDIENTE) {
            $this->addFlash('error', 'El traslado ya fue tomado por otro chofer.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        if ($assignmentRepository->contarActivasPorSolicitud($solicitud) > 0) {
            $this->addFlash('error', 'El traslado ya se encuentra asignado.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        $asignacion = new TransferAssignment();
        $asignacion->setSolicitud($solicitud);
        $asignacion->setChofer($perfil);
        $solicitud->addAsignacion($asignacion);
        $asignacion->setEstado(TransferAssignment::ESTADO_CAPTURADO);
        $asignacion->setParadaActual(0);

        $solicitud->setEstado(TransferRequest::ESTADO_CAPTURADO);

        $this->em->persist($asignacion);
        $this->em->persist($solicitud);
        $this->em->flush();

        $this->addFlash('success', 'Traslado capturado correctamente.');

        return $this->redirectToRoute('app_driver_dashboard');
    }

    #[Route('/asignacion/{id}/parada', name: 'app_driver_assignment_next', methods: ['POST'])]
    public function nextStop(Request $request, TransferAssignment $asignacion): RedirectResponse
    {
        $usuario = $this->requireAuthenticatedUser();
        $this->assertAssignmentOwner($asignacion, $usuario);

        if (!$this->isCsrfTokenValid('next_stop_' . $asignacion->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        if (in_array($asignacion->getEstado(), [TransferAssignment::ESTADO_CANCELADO, TransferAssignment::ESTADO_COMPLETADO], true)) {
            $this->addFlash('error', 'El viaje ya no admite cambios.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        $totalParadas = $asignacion->getSolicitud()->getDestinos()->count();
        if ($totalParadas === 0) {
            $this->addFlash('error', 'El traslado no tiene paradas configuradas.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        if ($asignacion->getEstado() === TransferAssignment::ESTADO_CAPTURADO) {
            $asignacion->setEstado(TransferAssignment::ESTADO_EN_CURSO);
            $asignacion->getSolicitud()->setEstado(TransferRequest::ESTADO_EN_CURSO);
        }

        $completadas = $asignacion->getParadaActual();
        if ($completadas < $totalParadas) {
            $asignacion->setParadaActual($completadas + 1);
            $this->em->persist($asignacion);
            $this->em->flush();
            $this->addFlash('success', 'Se avanzó a la siguiente parada.');
        } else {
            $this->addFlash('warning', 'Ya visitaste todas las paradas configuradas.');
        }

        return $this->redirectToRoute('app_driver_dashboard');
    }

    #[Route('/asignacion/{id}/finalizar', name: 'app_driver_assignment_complete', methods: ['POST'])]
    public function complete(Request $request, TransferAssignment $asignacion, DriverBalanceService $balanceService): RedirectResponse
    {
        $usuario = $this->requireAuthenticatedUser();
        $this->assertAssignmentOwner($asignacion, $usuario);

        if (!$this->isCsrfTokenValid('complete_transfer_' . $asignacion->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        if ($asignacion->getEstado() === TransferAssignment::ESTADO_COMPLETADO) {
            $this->addFlash('info', 'El viaje ya estaba finalizado.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        $asignacion->setEstado(TransferAssignment::ESTADO_COMPLETADO);
        $asignacion->setFinalizadoEn(new \DateTime());
        $asignacion->getSolicitud()->setEstado(TransferRequest::ESTADO_COMPLETADO);

        $this->em->persist($asignacion);
        $this->em->persist($asignacion->getSolicitud());
        $this->em->flush();

        $balanceService->recordTransferCompletion($asignacion);

        $this->addFlash('success', 'Traslado finalizado. ¡Gracias!');

        return $this->redirectToRoute('app_driver_dashboard');
    }

    #[Route('/asignacion/{id}/cancelar', name: 'app_driver_assignment_cancel', methods: ['POST'])]
    public function cancel(Request $request, TransferAssignment $asignacion, TransferAssignmentRepository $assignmentRepository): RedirectResponse
    {
        $usuario = $this->requireAuthenticatedUser();
        $this->assertAssignmentOwner($asignacion, $usuario);

        if (!$this->isCsrfTokenValid('cancel_transfer_' . $asignacion->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        if ($asignacion->getEstado() === TransferAssignment::ESTADO_COMPLETADO) {
            $this->addFlash('error', 'No se puede cancelar un viaje ya finalizado.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        $asignacion->setEstado(TransferAssignment::ESTADO_CANCELADO);
        $asignacion->setFinalizadoEn(new \DateTime());
        $this->em->persist($asignacion);

        $solicitud = $asignacion->getSolicitud();
        if ($assignmentRepository->contarActivasPorSolicitud($solicitud) <= 1) {
            $solicitud->setEstado(TransferRequest::ESTADO_PENDIENTE);
            $this->em->persist($solicitud);
        }

        $this->em->flush();

        $this->addFlash('success', 'Liberaste el traslado.');

        return $this->redirectToRoute('app_driver_dashboard');
    }

    #[Route('/asignacion/{id}/notas', name: 'app_driver_assignment_notes', methods: ['POST'])]
    public function updateNotes(Request $request, TransferAssignment $asignacion): RedirectResponse
    {
        $usuario = $this->requireAuthenticatedUser();
        $this->assertAssignmentOwner($asignacion, $usuario);

        if (!$this->isCsrfTokenValid('notes_transfer_' . $asignacion->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        $nota = trim((string) $request->request->get('nota'));
        $asignacion->setNotas($nota !== '' ? $nota : null);
        $this->em->persist($asignacion);
        $this->em->flush();

        $this->addFlash('success', 'Notas actualizadas.');

        return $this->redirectToRoute('app_driver_dashboard');
    }

    #[Route('/solicitud/{id}/pago-efectivo', name: 'app_driver_transfer_cash', methods: ['POST'])]
    public function updateCashPayment(
        Request $request,
        TransferRequest $solicitud,
        CashPaymentRepository $cashPayments,
        DriverBalanceService $balanceService
    ): RedirectResponse {
        $usuario = $this->requireAuthenticatedUser();
        $perfil = $this->requireApprovedProfile($usuario);

        if (!$this->isCsrfTokenValid('driver_cash_' . $solicitud->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token inválido.');
        }

        $asignado = false;
        foreach ($solicitud->getAsignaciones() as $asignacion) {
            if ($asignacion->getChofer()?->getId() === $perfil->getId()) {
                $asignado = true;
                break;
            }
        }

        if (!$asignado) {
            throw $this->createAccessDeniedException('No podés administrar el pago en efectivo de este traslado.');
        }

        $cashPayment = $cashPayments->findLatestForTransfer($solicitud);
        if (!$cashPayment instanceof CashPayment) {
            $this->addFlash('error', 'No se encontró un pago en efectivo asociado a este traslado.');

            return $this->redirectToRoute('app_driver_dashboard');
        }

        $action = (string) $request->request->get('action', '');

        if ($action === 'report') {
            $cashPayment->setStatus(CashPayment::STATUS_DRIVER_REPORTED);
            $cashPayment->setDriverReportedBy($perfil);
            $cashPayment->setDriverReportedAt(new \DateTimeImmutable());
            $cashPayment->setAdminConfirmedAt(null);
            $this->em->persist($cashPayment);

            $balanceService->recordCashDelivery($cashPayment, $perfil, $usuario);
            $this->addFlash('success', 'Registraste la entrega de efectivo al administrador.');
        } elseif ($action === 'revert') {
            $cashPayment->setStatus(CashPayment::STATUS_PENDING);
            $cashPayment->setDriverReportedAt(null);
            $cashPayment->setDriverReportedBy(null);
            $cashPayment->setAdminConfirmedAt(null);
            $this->em->persist($cashPayment);

            $balanceService->removeCashDelivery($cashPayment);
            $this->addFlash('info', 'Se revirtió el registro de entrega en efectivo.');
        } else {
            $this->addFlash('error', 'Acción de pago en efectivo no reconocida.');
        }

        return $this->redirectToRoute('app_driver_dashboard');
    }

    #[Route('/balance', name: 'app_driver_balance', methods: ['GET', 'POST'])]
    public function balance(
        Request $request,
        DriverBalanceService $balanceService,
        DriverBalanceEntryRepository $entryRepository,
        DriverWithdrawalRequestRepository $withdrawals
    ): Response {
        $usuario = $this->requireAuthenticatedUser();
        $perfil = $this->requireApprovedProfile($usuario);

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', '');

            if ($action === 'update_bank') {
                $perfil->setCbu($request->request->get('cbu'));
                $perfil->setCvu($request->request->get('cvu'));
                $perfil->setBankAlias($request->request->get('alias'));
                $this->em->persist($perfil);
                $this->em->flush();

                $this->addFlash('success', 'Datos bancarios actualizados.');

                return $this->redirectToRoute('app_driver_balance');
            }

            if ($action === 'request_withdrawal') {
                $amount = (float) $request->request->get('amount', 0);
                $notes = trim((string) $request->request->get('notes'));
                $stats = $balanceService->buildDriverBalance($perfil);

                if ($perfil->getCbu() === null && $perfil->getCvu() === null && $perfil->getBankAlias() === null) {
                    $this->addFlash('error', 'Debes configurar tu CBU, CVU o alias antes de solicitar un retiro.');

                    return $this->redirectToRoute('app_driver_balance');
                }

                if ($amount <= 0) {
                    $this->addFlash('error', 'Ingresá un monto válido para retirar.');

                    return $this->redirectToRoute('app_driver_balance');
                }

                if ($amount > $stats['available']) {
                    $this->addFlash('error', 'No hay saldo disponible suficiente para este retiro.');

                    return $this->redirectToRoute('app_driver_balance');
                }

                $retiro = (new DriverWithdrawalRequest())
                    ->setDriver($perfil)
                    ->setAmount($amount)
                    ->setCurrency('ARS')
                    ->setStatus(DriverWithdrawalRequest::STATUS_PENDING)
                    ->setNotes($notes !== '' ? $notes : null)
                    ->setRequestedBy($usuario);

                $this->em->persist($retiro);
                $this->em->flush();

                $this->addFlash('success', 'Solicitud de retiro enviada. El administrador la revisará a la brevedad.');

                return $this->redirectToRoute('app_driver_balance');
            }
        }

        $stats = $balanceService->buildDriverBalance($perfil);
        $entries = $entryRepository->findRecentForDriver($perfil, 50);
        $solicitudes = $withdrawals->findRecentForDriver($perfil, 20);

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        return $this->render('driver/balance.html.twig', [
            'usuario' => $usuario,
            'perfil' => $perfil,
            'stats' => $stats,
            'entries' => $entries,
            'solicitudes' => $solicitudes,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'plataforma' => $plataforma,
        ]);
    }

    #[Route('/balance/export', name: 'app_driver_balance_export', methods: ['GET'])]
    public function exportBalance(
        DriverBalanceEntryRepository $entryRepository,
        DriverWithdrawalRequestRepository $withdrawals,
        DriverBalanceService $balanceService,
        Pdf $pdf
    ): PdfResponse
    {
        $usuario = $this->requireAuthenticatedUser();
        $perfil = $this->requireApprovedProfile($usuario);

        $entries = $entryRepository->findRecentForDriver($perfil, 250);
        $solicitudes = $withdrawals->findRecentForDriver($perfil, 50);
        $stats = $balanceService->buildDriverBalance($perfil);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        $html = $this->renderView('pdf_generator/driver/balance.html.twig', [
            'driver' => $perfil,
            'entries' => $entries,
            'solicitudes' => $solicitudes,
            'stats' => $stats,
            'plataforma' => $plataforma,
            'generadoEn' => new \DateTimeImmutable(),
            'emitidoPara' => $usuario,
            'esAdministrador' => false,
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
            sprintf('balance-chofer-%s.pdf', $perfil->getId())
        );
    }

    private function requireAuthenticatedUser(): Usuario
    {
        $usuario = $this->getUser();
        if (!$usuario instanceof Usuario) {
            throw $this->createAccessDeniedException('Debes iniciar sesión para continuar.');
        }

        return $usuario;
    }

    private function requireApprovedProfile(Usuario $usuario): DriverProfile
    {
        $perfil = $this->em->getRepository(DriverProfile::class)->findOneBy(['usuario' => $usuario]);
        if (!$perfil instanceof DriverProfile || !$perfil->isAprobado()) {
            throw $this->createAccessDeniedException('Tu perfil de chofer no está habilitado.');
        }

        return $perfil;
    }

    private function assertAssignmentOwner(TransferAssignment $asignacion, Usuario $usuario): void
    {
        $chofer = $asignacion->getChofer();
        if (!$chofer instanceof DriverProfile || $chofer->getUsuario()?->getId() !== $usuario->getId()) {
            throw $this->createAccessDeniedException('No podés gestionar este traslado.');
        }
    }
}
