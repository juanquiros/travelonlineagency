<?php

namespace App\Controller;

use App\Entity\Plataforma;
use App\Entity\TransferDestination;
use App\Repository\TransferComboRepository;
use App\Repository\TransferDestinationCategoryRepository;
use App\Repository\TransferDestinationRepository;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DestinoController extends AbstractController
{
    public function __construct(
        private readonly TransferDestinationRepository $destinoRepository,
        private readonly TransferDestinationCategoryRepository $categoryRepository,
        private readonly TransferComboRepository $comboRepository,
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

        $searchTerm = trim((string) $request->query->get('q', ''));
        $categoriaId = $request->query->has('categoria') ? (int) $request->query->get('categoria') : null;
        if (null !== $categoriaId && $categoriaId <= 0) {
            $categoriaId = null;
        }

        $destinos = $this->destinoRepository->search($searchTerm !== '' ? $searchTerm : null, $categoriaId);
        $combos = $this->comboRepository->findActivosConDestinos();
        $categorias = $this->categoryRepository->findWithActiveDestinations();

        $categoriaNombreSeleccionada = null;
        if (null !== $categoriaId) {
            foreach ($categorias as $categoria) {
                if ($categoria->getId() === $categoriaId) {
                    $categoriaNombreSeleccionada = $categoria->getNombre();
                    break;
                }
            }
        }

        return $this->render('frontend/destinos.html.twig', [
            'destinos' => $destinos,
            'combos' => $combos,
            'categorias' => $categorias,
            'term' => $searchTerm,
            'categoriaSeleccionada' => $categoriaId,
            'categoriaSeleccionadaNombre' => $categoriaNombreSeleccionada,
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

        $combos = $this->comboRepository->findActivosPorDestino($destino);

        $shareUrl = $this->generateUrl('app_destino_show', ['id' => $destino->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->render('frontend/destino_show.html.twig', [
            'destino' => $destino,
            'destinosRelacionados' => array_values($relacionados),
            'combos' => $combos,
            'plataforma' => $plataforma,
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'usuario' => $usuario,
            'shareUrl' => $shareUrl,
            'mapDefaults' => [
                'lat' => -25.5972,
                'lng' => -54.5781,
            ],
        ]);
    }
}
