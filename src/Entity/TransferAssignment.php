<?php

namespace App\Entity;

use App\Repository\TransferAssignmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferAssignmentRepository::class)]
#[ORM\Table(name: 'transfer_assignment')]
class TransferAssignment
{
    public const ESTADO_CAPTURADO = 'capturado';
    public const ESTADO_EN_CURSO = 'en_curso';
    public const ESTADO_COMPLETADO = 'completado';
    public const ESTADO_CANCELADO = 'cancelado';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'asignaciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferRequest $solicitud = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?DriverProfile $chofer = null;

    #[ORM\Column(length: 20)]
    private string $estado = self::ESTADO_CAPTURADO;

    #[ORM\Column]
    private int $paradaActual = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notas = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finalizadoEn = null;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSolicitud(): ?TransferRequest
    {
        return $this->solicitud;
    }

    public function setSolicitud(TransferRequest $solicitud): self
    {
        $this->solicitud = $solicitud;

        return $this;
    }

    public function getChofer(): ?DriverProfile
    {
        return $this->chofer;
    }

    public function setChofer(DriverProfile $chofer): self
    {
        $this->chofer = $chofer;

        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): self
    {
        $this->estado = $estado;
        $this->touch();

        return $this;
    }

    public function getParadaActual(): int
    {
        return $this->paradaActual;
    }

    public function setParadaActual(int $paradaActual): self
    {
        $this->paradaActual = $paradaActual;
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

    public function getFinalizadoEn(): ?\DateTimeInterface
    {
        return $this->finalizadoEn;
    }

    public function setFinalizadoEn(?\DateTimeInterface $finalizadoEn): self
    {
        $this->finalizadoEn = $finalizadoEn;
        $this->touch();

        return $this;
    }
}
