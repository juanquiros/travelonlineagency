<?php

namespace App\Entity;

use App\Repository\MonedaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MonedaRepository::class)]
class Moneda
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 10)]
    private ?string $simbolo = null;

    #[ORM\Column]
    private ?float $habilitada = null;

    /**
     * @var Collection<int, Lenguaje>
     */
    #[ORM\OneToMany(targetEntity: Lenguaje::class, mappedBy: 'moneda_def')]
    private Collection $lenguajes;

    /**
     * @var Collection<int, Precio>
     */
    #[ORM\OneToMany(targetEntity: Precio::class, mappedBy: 'moneda', orphanRemoval: true)]
    private Collection $precios;

    /**
     * @param string|null $nombre
     * @param string|null $simbolo
     * @param float|null $habilitada
     */
    public function __construct(?string $nombre, ?string $simbolo, ?float $habilitada)
    {
        $this->nombre = $nombre;
        $this->simbolo = $simbolo;
        $this->habilitada = $habilitada;
        $this->lenguajes = new ArrayCollection();
        $this->precios = new ArrayCollection();
    }

    /**
     * @param float|null $habilitada
     */



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getSimbolo(): ?string
    {
        return $this->simbolo;
    }

    public function setSimbolo(string $simbolo): static
    {
        $this->simbolo = $simbolo;

        return $this;
    }

    public function getHabilitada(): ?float
    {
        return $this->habilitada;
    }

    public function setHabilitada(float $habilitada): static
    {
        $this->habilitada = $habilitada;

        return $this;
    }

    /**
     * @return Collection<int, Lenguaje>
     */
    public function getLenguajes(): Collection
    {
        return $this->lenguajes;
    }

    public function addLenguaje(Lenguaje $lenguaje): static
    {
        if (!$this->lenguajes->contains($lenguaje)) {
            $this->lenguajes->add($lenguaje);
            $lenguaje->setMonedaDef($this);
        }

        return $this;
    }

    public function removeLenguaje(Lenguaje $lenguaje): static
    {
        if ($this->lenguajes->removeElement($lenguaje)) {
            // set the owning side to null (unless already changed)
            if ($lenguaje->getMonedaDef() === $this) {
                $lenguaje->setMonedaDef(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Precio>
     */
    public function getPrecios(): Collection
    {
        return $this->precios;
    }

    public function addPrecio(Precio $precio): static
    {
        if (!$this->precios->contains($precio)) {
            $this->precios->add($precio);
            $precio->setMoneda($this);
        }

        return $this;
    }

    public function removePrecio(Precio $precio): static
    {
        if ($this->precios->removeElement($precio)) {
            // set the owning side to null (unless already changed)
            if ($precio->getMoneda() === $this) {
                $precio->setMoneda(null);
            }
        }

        return $this;
    }

    public function getMonedaInArray():array{
        return ['id'=>$this->id, 'nombre'=>$this->nombre,'simbolo'=>$this->simbolo,'habilitada'=>$this->habilitada];
    }
}
