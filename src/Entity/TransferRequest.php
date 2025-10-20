<?php

namespace App\Entity;

use App\Repository\TransferRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferRequestRepository::class)]
#[ORM\Table(name: 'transfer_request')]
class TransferRequest
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CAPTURADO = 'capturado';
    public const ESTADO_EN_CURSO = 'en_curso';
    public const ESTADO_COMPLETADO = 'completado';
    public const ESTADO_CANCELADO = 'cancelado';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $tipo = 'combo';

    #[ORM\ManyToOne]
    private ?TransferCombo $combo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $precioTotal = '0.00';

    #[ORM\Column(length: 3)]
    private string $moneda = 'ARS';

    #[ORM\Column(length: 150)]
    private string $nombrePasajero = '';

    #[ORM\Column(length: 150)]
    private string $emailPasajero = '';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telefonoPasajero = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $arribo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $salida = null;

    #[ORM\Column(length: 20)]
    private string $estado = self::ESTADO_PENDIENTE;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $datosExtra = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $tokenSeguimiento = null;

    #[ORM\ManyToOne]
    private ?Usuario $usuario = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notasCliente = null;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    /**
     * @var Collection<int, TransferRequestDestination>
     */
    #[ORM\OneToMany(mappedBy: 'solicitud', targetEntity: TransferRequestDestination::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['posicion' => 'ASC'])]
    private Collection $destinos;

    /**
     * @var Collection<int, TransferRequestFieldValue>
     */
    #[ORM\OneToMany(mappedBy: 'solicitud', targetEntity: TransferRequestFieldValue::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $valores;

    /**
     * @var Collection<int, TransferAssignment>
     */
    #[ORM\OneToMany(mappedBy: 'solicitud', targetEntity: TransferAssignment::class, cascade: ['persist', 'remove'])]
    private Collection $asignaciones;

    public function __construct()
    {
        $this->destinos = new ArrayCollection();
        $this->valores = new ArrayCollection();
        $this->asignaciones = new ArrayCollection();
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getCombo(): ?TransferCombo
    {
        return $this->combo;
    }

    public function setCombo(?TransferCombo $combo): self
    {
        $this->combo = $combo;

        return $this;
    }

    public function getPrecioTotal(): string
    {
        return $this->precioTotal;
    }

    public function setPrecioTotal(string $precioTotal): self
    {
        $this->precioTotal = $precioTotal;
        $this->touch();

        return $this;
    }

    public function getMoneda(): string
    {
        return $this->moneda;
    }

    public function setMoneda(string $moneda): self
    {
        $this->moneda = strtoupper($moneda);

        return $this;
    }

    public function getNombrePasajero(): string
    {
        return $this->nombrePasajero;
    }

    public function setNombrePasajero(string $nombrePasajero): self
    {
        $this->nombrePasajero = $nombrePasajero;

        return $this;
    }

    public function getEmailPasajero(): string
    {
        return $this->emailPasajero;
    }

    public function setEmailPasajero(string $emailPasajero): self
    {
        $this->emailPasajero = $emailPasajero;

        return $this;
    }

    public function getTelefonoPasajero(): ?string
    {
        return $this->telefonoPasajero;
    }

    public function setTelefonoPasajero(?string $telefonoPasajero): self
    {
        $this->telefonoPasajero = $telefonoPasajero;

        return $this;
    }

    public function getArribo(): ?\DateTimeInterface
    {
        return $this->arribo;
    }

    public function setArribo(?\DateTimeInterface $arribo): self
    {
        $this->arribo = $arribo;
        $this->touch();

        return $this;
    }

    public function getSalida(): ?\DateTimeInterface
    {
        return $this->salida;
    }

    public function setSalida(?\DateTimeInterface $salida): self
    {
        $this->salida = $salida;
        $this->touch();

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

    public function getDatosExtra(): ?array
    {
        return $this->datosExtra;
    }

    public function setDatosExtra(?array $datosExtra): self
    {
        $this->datosExtra = $datosExtra;
        $this->touch();

        return $this;
    }

    public function getTokenSeguimiento(): ?string
    {
        return $this->tokenSeguimiento;
    }

    public function setTokenSeguimiento(?string $tokenSeguimiento): self
    {
        $this->tokenSeguimiento = $tokenSeguimiento;

        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): self
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getNotasCliente(): ?string
    {
        return $this->notasCliente;
    }

    public function setNotasCliente(?string $notasCliente): self
    {
        $this->notasCliente = $notasCliente;

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

    /**
     * @return Collection<int, TransferRequestDestination>
     */
    public function getDestinos(): Collection
    {
        return $this->destinos;
    }

    public function addDestino(TransferRequestDestination $destino): self
    {
        if (!$this->destinos->contains($destino)) {
            $this->destinos->add($destino);
            $destino->setSolicitud($this);
        }

        return $this;
    }

    public function removeDestino(TransferRequestDestination $destino): self
    {
        if ($this->destinos->removeElement($destino)) {
            if ($destino->getSolicitud() === $this) {
                $destino->setSolicitud(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TransferRequestFieldValue>
     */
    public function getValores(): Collection
    {
        return $this->valores;
    }

    public function addValor(TransferRequestFieldValue $valor): self
    {
        if (!$this->valores->contains($valor)) {
            $this->valores->add($valor);
            $valor->setSolicitud($this);
        }

        return $this;
    }

    public function removeValor(TransferRequestFieldValue $valor): self
    {
        if ($this->valores->removeElement($valor)) {
            if ($valor->getSolicitud() === $this) {
                $valor->setSolicitud(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TransferAssignment>
     */
    public function getAsignaciones(): Collection
    {
        return $this->asignaciones;
    }

    public function addAsignacion(TransferAssignment $asignacion): self
    {
        if (!$this->asignaciones->contains($asignacion)) {
            $this->asignaciones->add($asignacion);
            $asignacion->setSolicitud($this);
        }

        return $this;
    }

    public function removeAsignacion(TransferAssignment $asignacion): self
    {
        if ($this->asignaciones->removeElement($asignacion)) {
            if ($asignacion->getSolicitud() === $this) {
                $asignacion->setSolicitud(null);
            }
        }

        return $this;
    }
}
