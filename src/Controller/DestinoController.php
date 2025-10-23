<?php

namespace App\Controller;

use App\Entity\Plataforma;
use App\Entity\TransferDestination;
use App\Repository\TransferDestinationRepository;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DestinoController extends AbstractController
{
    public function __construct(
        private readonly TransferDestinationRepository $destinoRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/destinos', name: 'app_destinos', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $usuario = $this->getUser();

        $destinos = $this->destinoRepository->findActivosConCategoria();

        return $this->render('frontend/destinos.html.twig', [
            'destinos' => $destinos,
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $usuario,
        ]);
    }

    #[Route('/destinos/{id}', name: 'app_destino_show', methods: ['GET'])]
    public function show(TransferDestination $destino, Request $request): Response
    {
        if (!$destino->isActivo()) {
            throw $this->createNotFoundException('Destino no disponible');
        }

        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);
        $usuario = $this->getUser();

        $relacionados = array_filter(
            $this->destinoRepository->findActivosConCategoria(),
            static fn (TransferDestination $item) => $item->getId() !== $destino->getId()
        );

        return $this->render('frontend/destino_show.html.twig', [
            'destino' => $destino,
            'destinosRelacionados' => array_values($relacionados),
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $usuario,
            'mapDefaults' => [
                'lat' => -25.5972,
                'lng' => -54.5781,
            ],
        ]);
    }
}
