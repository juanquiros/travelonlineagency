<?php

namespace App\Entity;

use App\Repository\DestinoCategoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DestinoCategoriaRepository::class)]
#[ORM\Table(name: 'destino_categoria')]
class DestinoCategoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $nombre = '';

    #[ORM\Column(length: 255)]
    private string $icono = '';

    /**
     * @var Collection<int, Destino>
     */
    #[ORM\OneToMany(mappedBy: 'categoria', targetEntity: Destino::class)]
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

    public function getIcono(): string
    {
        return $this->icono;
    }

    public function setIcono(string $icono): self
    {
        $this->icono = $icono;

        return $this;
    }

    /**
     * @return Collection<int, Destino>
     */
    public function getDestinos(): Collection
    {
        return $this->destinos;
    }

    public function addDestino(Destino $destino): self
    {
        if (!$this->destinos->contains($destino)) {
            $this->destinos->add($destino);
            $destino->setCategoria($this);
        }

        return $this;
    }

    public function removeDestino(Destino $destino): self
    {
        if ($this->destinos->removeElement($destino)) {
            if ($destino->getCategoria() === $this) {
                $destino->setCategoria(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre;
    }
}
