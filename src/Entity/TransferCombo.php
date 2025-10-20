<?php

namespace App\Entity;

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

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagenPortada = null;

    /**
     * @var Collection<int, TransferComboDestination>
     */
    #[ORM\OneToMany(mappedBy: 'combo', targetEntity: TransferComboDestination::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['posicion' => 'ASC'])]
    private Collection $destinos;

    public function __construct()
    {
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
