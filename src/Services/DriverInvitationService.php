<?php

namespace App\Services;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DriverInvitationService
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
    ) {
    }

    public function generateInviteCode(): string
    {
        return substr(hash('sha256', $this->appSecret . 'driver-invite'), 0, 32);
    }

    public function isValidCode(?string $code): bool
    {
        if ($code === null) {
            return false;
        }

        return hash_equals($this->generateInviteCode(), $code);
    }

    public function generateInviteLink(UrlGeneratorInterface $urlGenerator): string
    {
        return $urlGenerator->generate(
            'app_register_driver_invite',
            ['code' => $this->generateInviteCode()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
