<?php

namespace App\Controller\Api;

use App\Entity\Destino;
use App\Repository\DestinoRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/destinos', name: 'api_destinos_')]
class ApiDestinoController extends AbstractController
{
    public function __construct(
        private readonly DestinoRepository $destinoRepository,
        private readonly Packages $assetPackages,
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $destinos = $this->destinoRepository->findActivos();

        return $this->json(array_map(fn (Destino $destino) => $this->serializeDestino($destino, $urlGenerator), $destinos));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(?Destino $destino, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        if (!$destino instanceof Destino || !$destino->isActivo()) {
            return $this->json(['message' => 'Destino no disponible'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeDestino($destino, $urlGenerator));
    }

    private function serializeDestino(Destino $destino, UrlGeneratorInterface $urlGenerator): array
    {
        $imagenUrl = null;
        if ($destino->getImagenPrincipal()) {
            $relative = $this->assetPackages->getUrl('img/destinos/' . $destino->getImagenPrincipal());
            $base = rtrim($urlGenerator->generate('app_inicio', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');
            $imagenUrl = str_starts_with($relative, 'http') ? $relative : $base . '/' . ltrim($relative, '/');
        }

        return [
            'id' => $destino->getId(),
            'nombre' => $destino->getNombre(),
            'direccion' => $destino->getDireccion(),
            'lat' => $destino->getCoordenadasLat(),
            'lng' => $destino->getCoordenadasLng(),
            'descripcionCorta' => $destino->getDescripcionCorta(),
            'descripcionDetallada' => $destino->getDescripcionDetallada(),
            'categoria' => [
                'id' => $destino->getCategoria()->getId(),
                'nombre' => $destino->getCategoria()->getNombre(),
                'icono' => $destino->getCategoria()->getIcono(),
            ],
            'imagen' => $imagenUrl,
            'activo' => $destino->isActivo(),
        ];
    }
}
