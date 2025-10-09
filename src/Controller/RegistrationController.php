<?php

namespace App\Controller;

use App\Entity\BookingPartner;
use App\Entity\Plataforma;
use App\Entity\Usuario;
use App\Form\RegistrationFormType;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new Usuario();
        $form = $this->createForm(RegistrationFormType::class, $user);
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

            if ($form->get('solicitarPartner')->getData()) {
                $partner = new BookingPartner();
                $partner->setUsuario($user);
                $partner->setHabilitado(false);
                $entityManager->persist($partner);
                $this->addFlash('success', 'Tu solicitud para ser partner fue enviada y está pendiente de aprobación.');
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
            'usuario'=>$usuario

        ]);
    }
}
