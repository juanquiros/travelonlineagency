<?php

namespace App\Entity;

use App\Repository\VehicleFeatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleFeatureRepository::class)]
class VehicleFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, unique: true)]
    private string $nombre = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $activo = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $actualizadoEn;

    /**
     * @var Collection<int, DriverProfile>
     */
    #[ORM\ManyToMany(targetEntity: DriverProfile::class, mappedBy: 'vehicleFeatures')]
    private Collection $drivers;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->creadoEn = $now;
        $this->actualizadoEn = $now;
        $this->drivers = new ArrayCollection();
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

    private function touch(): void
    {
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
