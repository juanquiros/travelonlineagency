<?php

namespace App\Entity;

use App\Repository\DetallePagoPayPalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetallePagoPayPalRepository::class)]
class DetallePagoPayPal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detallesPago')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PayPalPago $PayPalPago = null;

    #[ORM\Column(length: 255)]
    private ?string $captureId = null;


    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column]
    private array $seller_receivable_breakdown = [];

    public function getId(): ?int
    {
        return $this->id;
    }



    public function getPayPalPago(): ?PayPalPago
    {
        return $this->PayPalPago;
    }

    public function setPayPalPago(?PayPalPago $PayPalPago): static
    {
        $this->PayPalPago = $PayPalPago;

        return $this;
    }

    public function getCaptureId(): ?string
    {
        return $this->captureId;
    }

    public function setCaptureId(string $captureId): static
    {
        $this->captureId = $captureId;

        return $this;
    }



    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSellerReceivableBreakdown(): array
    {
        return $this->seller_receivable_breakdown;
    }

    public function setSellerReceivableBreakdown(array $seller_receivable_breakdown): static
    {
        $this->seller_receivable_breakdown = $seller_receivable_breakdown;

        return $this;
    }
}
