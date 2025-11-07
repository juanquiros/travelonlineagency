<?php

namespace App\Entity;

use App\Entity\Moneda;
use App\Entity\Precio;
use App\Repository\TransferComboRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferComboRepository::class)]
#[ORM\Table(name: 'transfer_combo')]
class TransferCombo
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
    private string $precio = '0.00';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Moneda $moneda = null;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagenPortada = null;

    /**
     * @var Collection<int, Precio>
     */
    #[ORM\OneToMany(mappedBy: 'transferCombo', targetEntity: Precio::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $precios;

    /**
     * @var Collection<int, TransferComboDestination>
     */
    #[ORM\OneToMany(mappedBy: 'combo', targetEntity: TransferComboDestination::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['posicion' => 'ASC'])]
    private Collection $destinos;

    public function __construct()
    {
        $this->precios = new ArrayCollection();
        $this->destinos = new ArrayCollection();
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

    public function getPrecio(): string
    {
        return $this->precio;
    }

    public function setPrecio(string $precio): self
    {
        $this->precio = $precio;

        return $this;
    }

    public function getMoneda(): ?Moneda
    {
        return $this->moneda;
    }

    public function setMoneda(?Moneda $moneda): self
    {
        $this->moneda = $moneda;

        return $this;
    }

    /**
     * @return Collection<int, Precio>
     */
    public function getPrecios(): Collection
    {
        return $this->precios;
    }

    public function addPrecio(Precio $precio): self
    {
        if (!$this->precios->contains($precio)) {
            $this->precios->add($precio);
            $precio->setTransferCombo($this);
        }

        return $this;
    }

    public function removePrecio(Precio $precio): self
    {
        if ($this->precios->removeElement($precio)) {
            if ($precio->getTransferCombo() === $this) {
                $precio->setTransferCombo(null);
            }
        }

        return $this;
    }

    public function getPrecioParaMoneda(?Moneda $moneda): ?float
    {
        if (!$moneda instanceof Moneda) {
            return null;
        }

        return $this->getPrecioParaIso($moneda->getCodigoIso() ?? $moneda->getSimbolo());
    }

    public function getPrecioParaIso(?string $iso): ?float
    {
        if ($iso === null) {
            return null;
        }

        $iso = strtoupper(substr($iso, 0, 3));

        foreach ($this->precios as $precio) {
            $currency = $precio->getMoneda();
            if (!$currency instanceof Moneda) {
                continue;
            }

            $currencyIso = $currency->getCodigoIso() ?? $currency->getSimbolo();
            if ($currencyIso !== null && strtoupper(substr($currencyIso, 0, 3)) === $iso) {
                return (float) $precio->getValor();
            }
        }

        if ($this->moneda instanceof Moneda) {
            $currencyIso = $this->moneda->getCodigoIso() ?? $this->moneda->getSimbolo();
            if ($currencyIso !== null && strtoupper(substr($currencyIso, 0, 3)) === $iso) {
                return (float) $this->precio;
            }
        }

        return null;
    }

    public function getPreciosDisponibles(): array
    {
        $available = [];

        foreach ($this->precios as $precio) {
            $currency = $precio->getMoneda();
            if (!$currency instanceof Moneda) {
                continue;
            }

            $iso = $currency->getCodigoIso() ?? $currency->getSimbolo();
            if ($iso === null) {
                continue;
            }

            $available[strtoupper(substr($iso, 0, 3))] = (float) $precio->getValor();
        }

        if ($this->moneda instanceof Moneda) {
            $iso = $this->moneda->getCodigoIso() ?? $this->moneda->getSimbolo();
            if ($iso !== null && !array_key_exists(strtoupper(substr($iso, 0, 3)), $available)) {
                $available[strtoupper(substr($iso, 0, 3))] = (float) $this->precio;
            }
        }

        return $available;
    }

    public function getPrimaryPrecio(): ?Precio
    {
        foreach ($this->precios as $precio) {
            if ($precio->getMoneda() instanceof Moneda) {
                return $precio;
            }
        }

        return null;
    }

    public function getDisplayPrice(?Moneda $preferred = null): ?array
    {
        if ($preferred instanceof Moneda) {
            $amount = $this->getPrecioParaMoneda($preferred);
            if ($amount !== null) {
                return ['amount' => $amount, 'currency' => $preferred];
            }
        }

        $primary = $this->getPrimaryPrecio();
        if ($primary instanceof Precio) {
            $currency = $primary->getMoneda();
            if ($currency instanceof Moneda) {
                return ['amount' => (float) $primary->getValor(), 'currency' => $currency];
            }
        }

        if ($this->moneda instanceof Moneda) {
            return ['amount' => (float) $this->precio, 'currency' => $this->moneda];
        }

        if ((float) $this->precio > 0) {
            return ['amount' => (float) $this->precio, 'currency' => null];
        }

        return null;
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

    public function getImagenPortada(): ?string
    {
        return $this->imagenPortada;
    }

    public function setImagenPortada(?string $imagenPortada): self
    {
        $this->imagenPortada = $imagenPortada;

        return $this;
    }

    /**
     * @return Collection<int, TransferComboDestination>
     */
    public function getDestinos(): Collection
    {
        return $this->destinos;
    }

    public function addDestino(TransferComboDestination $destino): self
    {
        if (!$this->destinos->contains($destino)) {
            $this->destinos->add($destino);
            $destino->setCombo($this);
        }

        return $this;
    }

    public function removeDestino(TransferComboDestination $destino): self
    {
        if ($this->destinos->removeElement($destino)) {
            if ($destino->getCombo() === $this) {
                $destino->setCombo(null);
            }
        }

        return $this;
    }
}
