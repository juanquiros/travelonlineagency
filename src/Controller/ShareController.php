<?php

namespace App\Controller;

use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
use Knp\Snappy\Image;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ShareController extends AbstractController
{
    public function __construct(
        private readonly Image $imageGenerator,
        private readonly Packages $assetPackages,
        private readonly AsciiSlugger $slugger,
    ) {
    }

    #[Route('/destinos/{id}/share/imagen', name: 'app_destino_share_image', methods: ['GET'])]
    public function destinoShareImage(TransferDestination $destino, UrlGeneratorInterface $urlGenerator): Response
    {
        if (!$destino->isActivo()) {
            throw $this->createNotFoundException();
        }

        $shareUrl = $urlGenerator->generate('app_destino_show', ['id' => $destino->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $imageUrl = $destino->getImagenPrincipal()
            ? $this->absoluteAsset($this->assetPackages->getUrl('img/destinos/' . $destino->getImagenPrincipal()), $urlGenerator)
            : null;

        $fallbackImageUrl = $this->absoluteAsset($this->assetPackages->getUrl('img/iguazu-hero.svg'), $urlGenerator);

        $html = $this->renderView('share/destino_share.html.twig', [
            'destino' => $destino,
            'shareUrl' => $shareUrl,
            'imageUrl' => $imageUrl,
            'fallbackImageUrl' => $fallbackImageUrl,
        ]);

        $output = $this->imageGenerator->getOutputFromHtml($html, [
            'format' => 'png',
            'quality' => 90,
            'width' => 1080,
            'height' => 1080,
        ]);

        $filename = sprintf('destino-%s.png', $this->slugger->slug($destino->getNombre())->lower());

        return new Response($output, Response::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/traslados/combos/{id}/share/imagen', name: 'app_transfer_combo_share_image', methods: ['GET'])]
    public function comboShareImage(TransferCombo $combo, UrlGeneratorInterface $urlGenerator): Response
    {
        if (!$combo->isActivo()) {
            throw $this->createNotFoundException();
        }

        $shareUrl = $urlGenerator->generate('app_transfer_combo_show', ['id' => $combo->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $imageUrl = $combo->getImagenPortada()
            ? $this->absoluteAsset($this->assetPackages->getUrl('img/transfer/' . $combo->getImagenPortada()), $urlGenerator)
            : null;

        $destinos = [];
        foreach ($combo->getDestinos() as $detalle) {
            $destino = $detalle->getDestino();
            if ($destino instanceof TransferDestination && $destino->isActivo()) {
                $destinos[] = $destino;
            }
        }

        $fallbackImageUrl = $this->absoluteAsset($this->assetPackages->getUrl('img/iguazu-hero.svg'), $urlGenerator);

        $html = $this->renderView('share/combo_share.html.twig', [
            'combo' => $combo,
            'destinos' => $destinos,
            'shareUrl' => $shareUrl,
            'imageUrl' => $imageUrl,
            'fallbackImageUrl' => $fallbackImageUrl,
        ]);

        $output = $this->imageGenerator->getOutputFromHtml($html, [
            'format' => 'png',
            'quality' => 90,
            'width' => 1200,
            'height' => 675,
        ]);

        $filename = sprintf('combo-%s.png', $this->slugger->slug($combo->getNombre())->lower());

        return new Response($output, Response::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function absoluteAsset(string $url, UrlGeneratorInterface $urlGenerator): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        $base = rtrim($urlGenerator->generate('app_inicio', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        return $base . '/' . ltrim($url, '/');
    }
}
