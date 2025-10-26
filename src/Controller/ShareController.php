<?php

namespace App\Controller;

use App\Entity\Plataforma;
use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
use App\Repository\PlataformaRepository;
use Knp\Snappy\Image;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ShareController extends AbstractController
{
    public function __construct(
        private readonly Image $imageGenerator,
        private readonly Packages $assetPackages,
        private readonly SluggerInterface $slugger,
        private readonly ParameterBagInterface $parameterBag,
        private readonly PlataformaRepository $plataformaRepository,
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
        $branding = $this->resolvePlatformBranding($urlGenerator);

        $destinoLogoUrl = null;
        if ($destino->getLogo()) {
            $logoRelative = 'img/destinos/logos/' . ltrim($destino->getLogo(), '/');
            $mime = $this->guessMimeType($logoRelative);

            if ($mime) {
                $destinoLogoUrl = $this->dataUriForPublicAsset($logoRelative, $mime);
            }

            if (!$destinoLogoUrl) {
                $destinoLogoUrl = $this->absoluteAsset($this->assetPackages->getUrl($logoRelative), $urlGenerator);
            }
        }

        $html = $this->renderView('share/destino_share.html.twig', [
            'destino' => $destino,
            'shareUrl' => $shareUrl,
            'imageUrl' => $imageUrl,
            'fallbackImageUrl' => $fallbackImageUrl,
            'platformIconUrl' => $branding['icon'],
            'platformName' => $branding['name'],
            'destinoLogoUrl' => $destinoLogoUrl,
        ]);

        $output = $this->imageGenerator->getOutputFromHtml($html, [
            'format' => 'png',
            'quality' => 90,
            'width' => 1080,
            'height' => 1920,
            'enable-local-file-access' => true,
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
        $branding = $this->resolvePlatformBranding($urlGenerator);

        $primaryAddress = null;
        foreach ($destinos as $destino) {
            if ($destino->getDireccion()) {
                $primaryAddress = $destino->getDireccion();
                break;
            }
        }

        $html = $this->renderView('share/combo_share.html.twig', [
            'combo' => $combo,
            'destinos' => $destinos,
            'shareUrl' => $shareUrl,
            'imageUrl' => $imageUrl,
            'fallbackImageUrl' => $fallbackImageUrl,
            'platformIconUrl' => $branding['icon'],
            'platformName' => $branding['name'],
            'primaryAddress' => $primaryAddress,
        ]);

        $output = $this->imageGenerator->getOutputFromHtml($html, [
            'format' => 'png',
            'quality' => 90,
            'width' => 1080,
            'height' => 1920,
            'enable-local-file-access' => true,
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

    private function dataUriForPublicAsset(string $relativePath, string $mimeType): ?string
    {
        $publicPath = $this->parameterBag->get('kernel.project_dir') . '/public/' . ltrim($relativePath, '/');

        if (!is_file($publicPath) || !is_readable($publicPath)) {
            return null;
        }

        $contents = file_get_contents($publicPath);

        if ($contents === false) {
            return null;
        }

        $base64 = base64_encode($contents);

        return sprintf('data:%s;base64,%s', $mimeType, $base64);
    }

    private function resolvePlatformBranding(UrlGeneratorInterface $urlGenerator): array
    {
        $plataforma = $this->plataformaRepository->find(1);
        $name = $plataforma instanceof Plataforma && $plataforma->getNombre()
            ? $plataforma->getNombre()
            : 'Travel Online Agency';

        $icon = null;
        if ($plataforma instanceof Plataforma && $plataforma->getIcono()) {
            $storedPath = $plataforma->getIcono();

            if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
                $icon = $storedPath;
            } else {
                $relativePath = ltrim($storedPath, '/');
                $mime = $this->guessMimeType($relativePath);

                if ($mime) {
                    $icon = $this->dataUriForPublicAsset($relativePath, $mime);
                }

                if (!$icon) {
                    $assetPath = '/' . $relativePath;
                    $icon = $this->absoluteAsset($this->assetPackages->getUrl($assetPath), $urlGenerator);
                }
            }
        }

        if (!$icon) {
            $icon = $this->dataUriForPublicAsset('img/logo-toa.svg', 'image/svg+xml')
                ?? $this->absoluteAsset($this->assetPackages->getUrl('img/logo-toa.svg'), $urlGenerator);
        }

        return [
            'name' => $name,
            'icon' => $icon,
        ];
    }

    private function guessMimeType(string $path): ?string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => null,
        };
    }
}
