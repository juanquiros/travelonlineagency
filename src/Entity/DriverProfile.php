<?php

namespace App\Entity;

use App\Repository\DriverProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DriverProfileRepository::class)]
#[ORM\Table(name: 'driver_profile')]
class DriverProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'driverProfile')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuario $usuario = null;

    #[ORM\Column(length: 150)]
    private string $nombreCompleto = '';

    #[ORM\Column(length: 50)]
    private string $documento = '';

    #[ORM\Column(length: 80)]
    private string $telefono = '';

    #[ORM\Column(length: 120)]
    private string $patente = '';

    #[ORM\Column(length: 120)]
    private string $modeloVehiculo = '';

    #[ORM\Column(length: 100)]
    private string $tipoVehiculo = '';

    #[ORM\ManyToOne(inversedBy: 'drivers')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?VehicleType $vehicleType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fotoVehiculo = null;

    #[ORM\Column]
    private bool $aprobado = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notas = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $commissionPercentage = '0.00';

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $cbu = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $cvu = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bankAlias = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    #[ORM\ManyToMany(targetEntity: VehicleFeature::class, inversedBy: 'drivers')]
    #[ORM\JoinTable(name: 'driver_profile_vehicle_feature')]
    private Collection $vehicleFeatures;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
        $this->vehicleFeatures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(Usuario $usuario): self
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getNombreCompleto(): string
    {
        return $this->nombreCompleto;
    }

    public function setNombreCompleto(string $nombreCompleto): self
    {
        $this->nombreCompleto = $nombreCompleto;
        $this->touch();

        return $this;
    }

    public function getDocumento(): string
    {
        return $this->documento;
    }

    public function setDocumento(string $documento): self
    {
        $this->documento = $documento;
        $this->touch();

        return $this;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): self
    {
        $this->telefono = $telefono;
        $this->touch();

        return $this;
    }

    public function getPatente(): string
    {
        return $this->patente;
    }

    public function setPatente(string $patente): self
    {
        $this->patente = $patente;
        $this->touch();

        return $this;
    }

    public function getModeloVehiculo(): string
    {
        return $this->modeloVehiculo;
    }

    public function setModeloVehiculo(string $modeloVehiculo): self
    {
        $this->modeloVehiculo = $modeloVehiculo;
        $this->touch();

        return $this;
    }

    public function getTipoVehiculo(): string
    {
        if ($this->vehicleType instanceof VehicleType) {
            return (string) $this->vehicleType->getNombre();
        }

        return $this->tipoVehiculo;
    }

    public function setTipoVehiculo(string $tipoVehiculo): self
    {
        $this->tipoVehiculo = $tipoVehiculo;
        if ($tipoVehiculo === '') {
            $this->vehicleType = null;
        }
        $this->touch();

        return $this;
    }

    public function getVehicleType(): ?VehicleType
    {
        return $this->vehicleType;
    }

    public function setVehicleType(?VehicleType $vehicleType): self
    {
        $this->vehicleType = $vehicleType;
        if ($vehicleType instanceof VehicleType) {
            $this->tipoVehiculo = (string) $vehicleType->getNombre();
        }
        $this->touch();

        return $this;
    }

    public function getFotoVehiculo(): ?string
    {
        return $this->fotoVehiculo;
    }

    public function setFotoVehiculo(?string $fotoVehiculo): self
    {
        $this->fotoVehiculo = $fotoVehiculo;
        $this->touch();

        return $this;
    }

    /**
     * @return Collection<int, VehicleFeature>
     */
    public function getVehicleFeatures(): Collection
    {
        return $this->vehicleFeatures;
    }

    public function addVehicleFeature(VehicleFeature $feature): self
    {
        if (!$this->vehicleFeatures->contains($feature)) {
            $this->vehicleFeatures->add($feature);
            $this->touch();
        }

        return $this;
    }

    public function removeVehicleFeature(VehicleFeature $feature): self
    {
        if ($this->vehicleFeatures->removeElement($feature)) {
            $this->touch();
        }

        return $this;
    }

    public function isAprobado(): bool
    {
        return $this->aprobado;
    }

    public function setAprobado(bool $aprobado): self
    {
        $this->aprobado = $aprobado;
        $this->touch();

        return $this;
    }

    public function getNotas(): ?string
    {
        return $this->notas;
    }

    public function setNotas(?string $notas): self
    {
        $this->notas = $notas;
        $this->touch();

        return $this;
    }

    public function getCommissionPercentage(): float
    {
        return (float) $this->commissionPercentage;
    }

    public function setCommissionPercentage(float|string $commissionPercentage): self
    {
        $value = max(0, min(100, (float) $commissionPercentage));
        $this->commissionPercentage = number_format($value, 2, '.', '');
        $this->touch();

        return $this;
    }

    public function getCbu(): ?string
    {
        return $this->cbu;
    }

    public function setCbu(?string $cbu): self
    {
        $this->cbu = $cbu ? substr(preg_replace('/\s+/', '', $cbu), 0, 32) : null;
        $this->touch();

        return $this;
    }

    public function getCvu(): ?string
    {
        return $this->cvu;
    }

    public function setCvu(?string $cvu): self
    {
        $this->cvu = $cvu ? substr(preg_replace('/\s+/', '', $cvu), 0, 32) : null;
        $this->touch();

        return $this;
    }

    public function getBankAlias(): ?string
    {
        return $this->bankAlias;
    }

    public function setBankAlias(?string $bankAlias): self
    {
        $this->bankAlias = $bankAlias ? substr(trim($bankAlias), 0, 50) : null;
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

    private function touch(): void
    {
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
