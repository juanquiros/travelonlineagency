<?php

namespace App\Entity;

use App\Repository\DriverBalanceEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DriverBalanceEntryRepository::class)]
class DriverBalanceEntry
{
    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    public const TYPE_EARNING = 'earning';
    public const TYPE_CASH_DELIVERY = 'cash_delivery';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_WITHDRAWAL_REQUEST = 'withdrawal_request';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?DriverProfile $driver = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 3)]
    private string $currency = 'ARS';

    #[ORM\Column(length: 16)]
    private string $direction = self::DIRECTION_CREDIT;

    #[ORM\Column(length: 40)]
    private string $type = self::TYPE_ADJUSTMENT;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    private ?Usuario $createdBy = null;

    #[ORM\OneToOne(inversedBy: 'driverBalanceEntry', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CashPayment $cashPayment = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): ?DriverProfile
    {
        return $this->driver;
    }

    public function setDriver(DriverProfile $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(float|string $amount): self
    {
        $value = max(0, (float) $amount);
        $this->amount = number_format($value, 2, '.', '');

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = strtoupper(substr($currency, 0, 3));

        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $direction = strtolower($direction);
        $this->direction = in_array($direction, [self::DIRECTION_CREDIT, self::DIRECTION_DEBIT], true)
            ? $direction
            : self::DIRECTION_CREDIT;

        return $this;
    }

    public function isCredit(): bool
    {
        return $this->direction === self::DIRECTION_CREDIT;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = substr($type, 0, 40);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference ? substr($reference, 0, 120) : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?Usuario
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Usuario $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCashPayment(): ?CashPayment
    {
        return $this->cashPayment;
    }

    public function setCashPayment(?CashPayment $cashPayment): self
    {
        if ($cashPayment === null) {
            if ($this->cashPayment && $this->cashPayment->getDriverBalanceEntry() === $this) {
                $this->cashPayment->setDriverBalanceEntry(null);
            }
            $this->cashPayment = null;

            return $this;
        }

        $this->cashPayment = $cashPayment;
        if ($cashPayment->getDriverBalanceEntry() !== $this) {
            $cashPayment->setDriverBalanceEntry($this);
        }

        return $this;
    }

    public function getSignedAmount(): float
    {
        $base = (float) $this->amount;

        return $this->isCredit() ? $base : -$base;
    }
}
