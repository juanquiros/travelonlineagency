<?php

namespace App\Entity;

use App\Repository\PushEndPointRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
#[ORM\Entity(repositoryClass: PushEndPointRepository::class)]
class PushEndPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pushEndPoints')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuario $usuario = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $suscripcion = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getSuscripcion(): ?string
    {
        return $this->suscripcion;
    }

    public function setSuscripcion(string $suscripcion): static
    {
        $this->suscripcion = $suscripcion;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
