<?php

namespace App\Controller;

use App\Entity\BookingPartner;
use App\Entity\DriverProfile;
use App\Entity\Plataforma;
use App\Entity\Usuario;
use App\Form\RegistrationFormType;
use App\Services\LanguageService;
use App\Services\PartnerInvitationService;
use App\Services\DriverInvitationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PartnerInvitationService $partnerInvitationService,
        private readonly DriverInvitationService $driverInvitationService,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        return $this->handleRegistration($request, $userPasswordHasher, $entityManager, $slugger, false, true, false);
    }

    #[Route('/register/partner/{code}', name: 'app_register_partner_invite')]
    public function registerPartner(
        string $code,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        if (!$this->partnerInvitationService->isValidCode($code)) {
            throw $this->createNotFoundException();
        }

        return $this->handleRegistration($request, $userPasswordHasher, $entityManager, $slugger, true, false, false);
    }

    #[Route('/register/driver/{code}', name: 'app_register_driver_invite')]
    public function registerDriver(
        string $code,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        if (!$this->driverInvitationService->isValidCode($code)) {
            throw $this->createNotFoundException();
        }

        return $this->handleRegistration($request, $userPasswordHasher, $entityManager, $slugger, false, false, true);
    }

    private function handleRegistration(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        bool $forcePartnerRequest,
        bool $showPartnerCheckbox,
        bool $driverMode
    ): Response {
        $user = new Usuario();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'show_partner_checkbox' => $showPartnerCheckbox,
            'driver_mode' => $driverMode,
        ]);
        $form->handleRequest($request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $idiomas = LanguageService::getLenguajes($this->em);
        $usuario = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);

            $shouldRequestPartner = $forcePartnerRequest && !$driverMode;

            if (!$forcePartnerRequest && $form->has('solicitarPartner')) {
                $shouldRequestPartner = (bool) $form->get('solicitarPartner')->getData();
            }

            if ($shouldRequestPartner) {
                $partner = new BookingPartner();
                $partner->setUsuario($user);
                $partner->setHabilitado(false);
                $entityManager->persist($partner);
                $this->addFlash('success', 'Tu solicitud para ser partner fue enviada y está pendiente de aprobación.');
            }

            if ($driverMode) {
                $driverProfile = new DriverProfile();
                $driverProfile->setUsuario($user);
                $driverProfile->setNombreCompleto((string) $user->getNombre());
                $driverProfile->setDocumento((string) $form->get('driverDocumento')->getData());
                $driverProfile->setTelefono((string) $form->get('driverTelefono')->getData());
                $driverProfile->setPatente((string) $form->get('driverPatente')->getData());
                $driverProfile->setModeloVehiculo((string) $form->get('driverModeloVehiculo')->getData());
                $driverProfile->setNotas($form->get('driverNotas')->getData());

                /** @var UploadedFile|null $foto */
                $foto = $form->get('driverFoto')->getData();
                if ($foto instanceof UploadedFile) {
                    $filename = $this->uploadDriverImage($foto, $slugger);
                    if ($filename) {
                        $driverProfile->setFotoVehiculo($filename);
                    }
                }

                $entityManager->persist($driverProfile);
                $this->addFlash('success', 'Tu perfil de chofer fue registrado y está pendiente de aprobación.');
            }

            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_administrador');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'plataforma'=>$plataforma,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'usuario'=>$usuario,
            'forcePartnerRequest' => $forcePartnerRequest,
            'driverMode' => $driverMode,

        ]);
    }

    private function uploadDriverImage(UploadedFile $file, SluggerInterface $slugger): ?string
    {
        $originalFilename = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.jpg';

        $targetDirectory = $this->resolveDriverDirectory();

        try {
            $resource = match ($file->getMimeType()) {
                'image/png' => imagecreatefrompng($file->getPathname()),
                'image/gif' => imagecreatefromgif($file->getPathname()),
                default => imagecreatefromjpeg($file->getPathname()),
            };
        } catch (\Throwable) {
            return null;
        }

        if (!$resource instanceof \GdImage) {
            return null;
        }

        $outputPath = $targetDirectory . DIRECTORY_SEPARATOR . $newFilename;

        try {
            if (!imagejpeg($resource, $outputPath, 80)) {
                return null;
            }
        } catch (\Throwable $exception) {
            throw new FileException($exception->getMessage(), 0, $exception);
        } finally {
            imagedestroy($resource);
        }

        return $newFilename;
    }

    private function resolveDriverDirectory(): string
    {
        $rawPath = (string) $this->getParameter('img_driver');

        if ($rawPath === '') {
            throw new FileException('No se configuró el directorio de imágenes de choferes.');
        }

        $normalised = preg_replace('#[\\\\/]+#', DIRECTORY_SEPARATOR, $rawPath) ?? $rawPath;
        $normalised = rtrim($normalised, DIRECTORY_SEPARATOR);

        if (!is_dir($normalised) && !@mkdir($normalised, 0775, true) && !is_dir($normalised)) {
            throw new FileException(sprintf('No se pudo crear el directorio "%s".', $normalised));
        }

        return $normalised;
    }
}
