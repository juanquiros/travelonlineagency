<?php

namespace App\Controller;

use App\Entity\DriverProfile;
use App\Entity\Plataforma;
use App\Entity\TransferAssignment;
use App\Entity\TransferRequest;
use App\Entity\Usuario;
use App\Repository\TransferAssignmentRepository;
use App\Repository\TransferRequestRepository;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function dashboard(Request $request, TransferAssignmentRepository $assignmentRepository, TransferRequestRepository $requestRepository): Response
    {
        $usuario = $this->requireAuthenticatedUser();
        $perfil = $this->em->getRepository(DriverProfile::class)->findOneBy(['usuario' => $usuario]);

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

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
        ]);
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
    public function complete(Request $request, TransferAssignment $asignacion): RedirectResponse
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
