<?php

namespace App\Entity;

use App\Repository\TransferRequestRepository;
use App\Entity\CashPayment;
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

    #[ORM\Column(type: Types::JSON)]
    private array $totalesPorMoneda = [];

    #[ORM\Column(length: 150)]
    private string $nombrePasajero = '';

    #[ORM\Column(length: 150)]
    private string $emailPasajero = '';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telefonoPasajero = null;

    #[ORM\Column(nullable: true)]
    private ?int $cantidadPasajeros = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vueloPasajero = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $tipoVehiculo = null;

    #[ORM\ManyToOne(inversedBy: 'transferRequests')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?VehicleType $vehicleType = null;

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

    #[ORM\Column(length: 40, unique: true, nullable: true)]
    private ?string $codigoServicio = null;

    #[ORM\ManyToOne]
    private ?Usuario $usuario = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notasCliente = null;

    #[ORM\Column(nullable: true)]
    private ?int $calificacion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $testimonioComentario = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $testimonioCreadoEn = null;

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

    /**
     * @var Collection<int, CashPayment>
     */
    #[ORM\OneToMany(mappedBy: 'transferRequest', targetEntity: CashPayment::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $cashPayments;

    public function __construct()
    {
        $this->destinos = new ArrayCollection();
        $this->valores = new ArrayCollection();
        $this->asignaciones = new ArrayCollection();
        $this->cashPayments = new ArrayCollection();
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

    public function getTotalesPorMoneda(): array
    {
        return is_array($this->totalesPorMoneda) ? $this->totalesPorMoneda : [];
    }

    public function setTotalesPorMoneda(array $totales): self
    {
        $normalizados = [];
        foreach ($totales as $iso => $valor) {
            $isoNormalizado = strtoupper(substr((string) $iso, 0, 3));
            if ($isoNormalizado === '') {
                continue;
            }

            $normalizados[$isoNormalizado] = (float) $valor;
        }

        $this->totalesPorMoneda = $normalizados;
        $this->touch();

        return $this;
    }

    public function getTotalParaIso(string $iso): ?float
    {
        $iso = strtoupper(substr($iso, 0, 3));
        $totales = $this->getTotalesPorMoneda();

        if (array_key_exists($iso, $totales)) {
            return (float) $totales[$iso];
        }

        if ($iso === strtoupper($this->moneda)) {
            return (float) $this->precioTotal;
        }

        return null;
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

    public function getCantidadPasajeros(): ?int
    {
        return $this->cantidadPasajeros;
    }

    public function setCantidadPasajeros(?int $cantidadPasajeros): self
    {
        $this->cantidadPasajeros = $cantidadPasajeros;
        $this->touch();

        return $this;
    }

    public function getVueloPasajero(): ?string
    {
        return $this->vueloPasajero;
    }

    public function setVueloPasajero(?string $vueloPasajero): self
    {
        $this->vueloPasajero = $vueloPasajero;
        $this->touch();

        return $this;
    }

    public function getTipoVehiculo(): ?string
    {
        if ($this->vehicleType instanceof VehicleType) {
            return $this->vehicleType->getNombre();
        }

        return $this->tipoVehiculo;
    }

    public function setTipoVehiculo(?string $tipoVehiculo): self
    {
        $this->tipoVehiculo = $tipoVehiculo !== null ? trim($tipoVehiculo) : null;
        if ($tipoVehiculo === null) {
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
            $this->tipoVehiculo = $vehicleType->getNombre();
        }
        $this->touch();

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

    public function getCodigoServicio(): ?string
    {
        return $this->codigoServicio;
    }

    public function setCodigoServicio(?string $codigoServicio): self
    {
        $this->codigoServicio = $codigoServicio;
        $this->touch();

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

    public function getCalificacion(): ?int
    {
        return $this->calificacion;
    }

    public function setCalificacion(?int $calificacion): self
    {
        if ($calificacion !== null) {
            $calificacion = max(1, min(5, $calificacion));
        }

        $this->calificacion = $calificacion;
        $this->touch();

        return $this;
    }

    public function getTestimonioComentario(): ?string
    {
        return $this->testimonioComentario;
    }

    public function setTestimonioComentario(?string $testimonioComentario): self
    {
        $this->testimonioComentario = $testimonioComentario;
        $this->touch();

        return $this;
    }

    public function getTestimonioCreadoEn(): ?\DateTimeImmutable
    {
        return $this->testimonioCreadoEn;
    }

    public function setTestimonioCreadoEn(?\DateTimeImmutable $testimonioCreadoEn): self
    {
        $this->testimonioCreadoEn = $testimonioCreadoEn;
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

    /**
     * @return Collection<int, CashPayment>
     */
    public function getCashPayments(): Collection
    {
        return $this->cashPayments;
    }

    public function addCashPayment(CashPayment $cashPayment): self
    {
        if (!$this->cashPayments->contains($cashPayment)) {
            $this->cashPayments->add($cashPayment);
            $cashPayment->setTransferRequest($this);
        }

        return $this;
    }

    public function removeCashPayment(CashPayment $cashPayment): self
    {
        if ($this->cashPayments->removeElement($cashPayment)) {
            if ($cashPayment->getTransferRequest() === $this) {
                $cashPayment->setTransferRequest(null);
            }
        }

        return $this;
    }
}
