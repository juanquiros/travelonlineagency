<?php

namespace App\Entity;

use App\Repository\CashPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CashPaymentRepository::class)]
class CashPayment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DRIVER_REPORTED = 'driver_reported';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 3)]
    private string $currency = 'ARS';

    #[ORM\Column(length: 32)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'cashPayments')]
    private ?SolicitudReserva $solicitudReserva = null;

    #[ORM\ManyToOne(inversedBy: 'cashPayments')]
    private ?TransferRequest $transferRequest = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $driverReportedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $adminConfirmedAt = null;

    #[ORM\ManyToOne]
    private ?DriverProfile $driverReportedBy = null;

    #[ORM\OneToOne(mappedBy: 'cashPayment', cascade: ['persist', 'remove'])]
    private ?DriverBalanceEntry $driverBalanceEntry = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->status = self::STATUS_PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(float|string $amount): self
    {
        $this->amount = number_format((float) $amount, 2, '.', '');
        $this->touch();

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = strtoupper(substr($currency, 0, 3));
        $this->touch();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSolicitudReserva(): ?SolicitudReserva
    {
        return $this->solicitudReserva;
    }

    public function setSolicitudReserva(?SolicitudReserva $solicitudReserva): self
    {
        $this->solicitudReserva = $solicitudReserva;
        $this->touch();

        return $this;
    }

    public function getTransferRequest(): ?TransferRequest
    {
        return $this->transferRequest;
    }

    public function setTransferRequest(?TransferRequest $transferRequest): self
    {
        $this->transferRequest = $transferRequest;
        $this->touch();

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        $this->touch();

        return $this;
    }

    public function getDriverReportedAt(): ?\DateTimeInterface
    {
        return $this->driverReportedAt;
    }

    public function setDriverReportedAt(?\DateTimeInterface $driverReportedAt): self
    {
        $this->driverReportedAt = $driverReportedAt;
        $this->touch();

        return $this;
    }

    public function getAdminConfirmedAt(): ?\DateTimeInterface
    {
        return $this->adminConfirmedAt;
    }

    public function setAdminConfirmedAt(?\DateTimeInterface $adminConfirmedAt): self
    {
        $this->adminConfirmedAt = $adminConfirmedAt;
        $this->touch();

        return $this;
    }

    public function getDriverReportedBy(): ?DriverProfile
    {
        return $this->driverReportedBy;
    }

    public function setDriverReportedBy(?DriverProfile $driverReportedBy): self
    {
        $this->driverReportedBy = $driverReportedBy;
        $this->touch();

        return $this;
    }

    public function getDriverBalanceEntry(): ?DriverBalanceEntry
    {
        return $this->driverBalanceEntry;
    }

    public function setDriverBalanceEntry(?DriverBalanceEntry $driverBalanceEntry): self
    {
        if ($driverBalanceEntry === null) {
            if ($this->driverBalanceEntry && $this->driverBalanceEntry->getCashPayment() === $this) {
                $this->driverBalanceEntry->setCashPayment(null);
            }
            $this->driverBalanceEntry = null;

            return $this;
        }

        $this->driverBalanceEntry = $driverBalanceEntry;
        if ($driverBalanceEntry->getCashPayment() !== $this) {
            $driverBalanceEntry->setCashPayment($this);
        }

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
