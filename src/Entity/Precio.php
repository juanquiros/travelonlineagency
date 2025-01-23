<?php

namespace App\Entity;

use App\Repository\PrecioRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrecioRepository::class)]
class Precio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $valor = null;

    #[ORM\ManyToOne(inversedBy: 'precios')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Moneda $moneda = null;

    #[ORM\ManyToOne(inversedBy: 'precios')]
    private ?Booking $booking = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValor(): ?float
    {
        return $this->valor;
    }

    public function setValor(float $valor): static
    {
        $this->valor = $valor;

        return $this;
    }

    public function getMoneda(): ?Moneda
    {
        return $this->moneda;
    }

    public function setMoneda(?Moneda $moneda): static
    {
        $this->moneda = $moneda;

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
