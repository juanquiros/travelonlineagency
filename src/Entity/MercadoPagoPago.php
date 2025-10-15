<?php

namespace App\Entity;

use App\Repository\MercadoPagoPagoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MercadoPagoPagoRepository::class)]
class MercadoPagoPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BIGINT)]
    private string $paymentId = '0';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $preferenceId = null;

    #[ORM\ManyToOne(inversedBy: 'mercadoPagoPagos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CredencialesMercadoPago $credencialesMercadoPago = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $collectorId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $payer = null;

    #[ORM\Column]
    private ?float $transactionAmount = 0;

    #[ORM\Column]
    private ?float $transactionAmountRefunded = 0;

    #[ORM\Column(length: 255)]
    private ?string $paymentMethodId = null;

    #[ORM\Column(length: 255)]
    private ?string $paymentTypeId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $card = null;

    #[ORM\Column]
    private ?float $netReceivedAmount = 0;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $feeDetails = null;

    #[ORM\Column(nullable: true)]
    private ?float $applicationFee = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'pagosMercadoPago')]
    private ?SolicitudReserva $solicitudReserva = null;



    /**
     * @param \DateTimeImmutable|null $updatedAt
     * @param \DateTimeImmutable|null $createdAt
     */
    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function setPaymentId(int $paymentId): static
    {
        $this->paymentId = $paymentId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPreferenceId(): ?string
    {
        return $this->preferenceId;
    }

    public function setPreferenceId(?string $preferenceId): static
    {
        $this->preferenceId = $preferenceId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCredencialesMercadoPago(): ?CredencialesMercadoPago
    {
        return $this->credencialesMercadoPago;
    }

    public function setCredencialesMercadoPago(?CredencialesMercadoPago $credencialesMercadoPago): static
    {
        $this->credencialesMercadoPago = $credencialesMercadoPago;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCollectorId(): ?string
    {
        return $this->collectorId;
    }

    public function setCollectorId(?string $collectorId): static
    {
        $this->collectorId = $collectorId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPayer(): ?string
    {
        return $this->payer;
    }

    public function setPayer(?string $payer): static
    {
        $this->payer = $payer;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTransactionAmount(): ?float
    {
        return $this->transactionAmount;
    }

    public function setTransactionAmount(float $transactionAmount): static
    {
        $this->transactionAmount = $transactionAmount;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTransactionAmountRefunded(): ?float
    {
        return $this->transactionAmountRefunded;
    }

    public function setTransactionAmountRefunded(float $transactionAmountRefunded): static
    {
        $this->transactionAmountRefunded = $transactionAmountRefunded;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): static
    {
        $this->paymentMethodId = $paymentMethodId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPaymentTypeId(): ?string
    {
        return $this->paymentTypeId;
    }

    public function setPaymentTypeId(string $paymentTypeId): static
    {
        $this->paymentTypeId = $paymentTypeId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCard(): ?string
    {
        return $this->card;
    }

    public function setCard(?string $card): static
    {
        $this->card = $card;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNetReceivedAmount(): ?float
    {
        return $this->netReceivedAmount;
    }

    public function setNetReceivedAmount(float $netReceivedAmount): static
    {
        $this->netReceivedAmount = $netReceivedAmount;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getFeeDetails(): ?string
    {
        return $this->feeDetails;
    }
    public function getFeeDetailsArray(): ?array
    {
        return json_decode($this->feeDetails);
    }

    public function setFeeDetails(string $feeDetails): static
    {
        $this->feeDetails = $feeDetails;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getApplicationFee(): ?float
    {
        return $this->applicationFee;
    }

    public function setApplicationFee(?float $applicationFee): static
    {
        $this->applicationFee = $applicationFee;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSolicitudReserva(): ?SolicitudReserva
    {
        return $this->solicitudReserva;
    }

    public function setSolicitudReserva(?SolicitudReserva $solicitudReserva): static
    {
        $this->solicitudReserva = $solicitudReserva;

        return $this;
    }


}
