<?php

namespace App\Entity;

use App\Repository\VehicleTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleTypeRepository::class)]
class VehicleType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $nombre = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $activo = true;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $orden = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $actualizadoEn;

    /**
     * @var Collection<int, DriverProfile>
     */
    #[ORM\OneToMany(mappedBy: 'vehicleType', targetEntity: DriverProfile::class)]
    private Collection $drivers;

    /**
     * @var Collection<int, TransferRequest>
     */
    #[ORM\OneToMany(mappedBy: 'vehicleType', targetEntity: TransferRequest::class)]
    private Collection $transferRequests;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->creadoEn = $now;
        $this->actualizadoEn = $now;
        $this->drivers = new ArrayCollection();
        $this->transferRequests = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getNombre() ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = trim($nombre);
        $this->touch();

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion !== null ? trim($descripcion) : null;
        $this->touch();

        return $this;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;
        $this->touch();

        return $this;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): self
    {
        $this->orden = $orden;
        $this->touch();

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    /**
     * @return Collection<int, DriverProfile>
     */
    public function getDrivers(): Collection
    {
        return $this->drivers;
    }

    /**
     * @return Collection<int, TransferRequest>
     */
    public function getTransferRequests(): Collection
    {
        return $this->transferRequests;
    }

    private function touch(): void
    {
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
