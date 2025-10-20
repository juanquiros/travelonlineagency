<?php

namespace App\Entity;

use App\Repository\DriverProfileRepository;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fotoVehiculo = null;

    #[ORM\Column]
    private bool $aprobado = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
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
