<?php

namespace App\EventSubscriber;

use App\Services\SnappyBinaryResolver;
use Knp\Snappy\Image;
use Knp\Snappy\Pdf;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SnappyBinaryConfiguratorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Pdf $pdf,
        private readonly Image $image,
        private readonly SnappyBinaryResolver $resolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['configureBinaries', 512],
        ];
    }

    public function configureBinaries(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->pdf->setBinary($this->resolver->getPdfBinary());
        $this->image->setBinary($this->resolver->getImageBinary());
    }
}
