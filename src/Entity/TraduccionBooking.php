<?php

namespace App\Entity;

use App\Repository\TraduccionBookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraduccionBookingRepository::class)]
class TraduccionBooking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $keyName = null;

    #[ORM\Column(length: 255)]
    private ?string $traduccion = null;

    #[ORM\ManyToOne(inversedBy: 'traduccionesBooking')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lenguaje $lenguaje = null;

    #[ORM\ManyToOne(inversedBy: 'traducciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

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

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }
}
