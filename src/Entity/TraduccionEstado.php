<?php

namespace App\Entity;

use App\Repository\TraduccionEstadoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraduccionEstadoRepository::class)]
class TraduccionEstado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $keyName = null;

    #[ORM\Column(length: 255)]
    private ?string $traduccion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lenguaje $lenguaje = null;

    #[ORM\ManyToOne(inversedBy: 'traduccionesEstado')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EstadoReserva $estadoReserva = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyName(): ?string
    {
        return $this->keyName;
    }

    public function setKeyName(string $keyName): static
    {
        $this->keyName = $keyName;

        return $this;
    }

    public function getTraduccion(): ?string
    {
        return $this->traduccion;
    }

    public function setTraduccion(string $traduccion): static
    {
        $this->traduccion = $traduccion;

        return $this;
    }

    public function getLenguaje(): ?Lenguaje
    {
        return $this->lenguaje;
    }

    public function setLenguaje(?Lenguaje $lenguaje): static
    {
        $this->lenguaje = $lenguaje;

        return $this;
    }

    public function getEstadoReserva(): ?EstadoReserva
    {
        return $this->estadoReserva;
    }

    public function setEstadoReserva(?EstadoReserva $estadoReserva): static
    {
        $this->estadoReserva = $estadoReserva;

        return $this;
    }
    public function getTraduccionesPorLenguaje(Lenguaje $lenguaje):?array
    {
        return null;
    }
}
