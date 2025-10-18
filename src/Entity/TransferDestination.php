<?php

namespace App\Entity;

use App\Repository\TransferDestinationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferDestinationRepository::class)]
#[ORM\Table(name: 'transfer_destination')]
class TransferDestination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nombre = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $tarifaBase = '0.00';

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    /**
     * @var Collection<int, TransferComboDestination>
     */
    #[ORM\OneToMany(mappedBy: 'destino', targetEntity: TransferComboDestination::class, orphanRemoval: true)]
    private Collection $combos;

    /**
     * @var Collection<int, TransferRequestDestination>
     */
    #[ORM\OneToMany(mappedBy: 'destino', targetEntity: TransferRequestDestination::class, orphanRemoval: true)]
    private Collection $solicitudes;

    public function __construct()
    {
        $this->combos = new ArrayCollection();
        $this->solicitudes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getTarifaBase(): string
    {
        return $this->tarifaBase;
    }

    public function setTarifaBase(string $tarifaBase): self
    {
        $this->tarifaBase = $tarifaBase;

        return $this;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): self
    {
        $this->activo = $activo;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return Collection<int, TransferComboDestination>
     */
    public function getCombos(): Collection
    {
        return $this->combos;
    }

    public function addCombo(TransferComboDestination $combo): self
    {
        if (!$this->combos->contains($combo)) {
            $this->combos->add($combo);
            $combo->setDestino($this);
        }

        return $this;
    }

    public function removeCombo(TransferComboDestination $combo): self
    {
        if ($this->combos->removeElement($combo)) {
            if ($combo->getDestino() === $this) {
                $combo->setDestino(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TransferRequestDestination>
     */
    public function getSolicitudes(): Collection
    {
        return $this->solicitudes;
    }

    public function addSolicitud(TransferRequestDestination $solicitud): self
    {
        if (!$this->solicitudes->contains($solicitud)) {
            $this->solicitudes->add($solicitud);
            $solicitud->setDestino($this);
        }

        return $this;
    }

    public function removeSolicitud(TransferRequestDestination $solicitud): self
    {
        if ($this->solicitudes->removeElement($solicitud)) {
            if ($solicitud->getDestino() === $this) {
                $solicitud->setDestino(null);
            }
        }

        return $this;
    }
}
