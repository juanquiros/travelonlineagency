<?php

namespace App\Entity;

use App\Repository\LenguajeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LenguajeRepository::class)]
class Lenguaje
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $codigo = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $icono = null;

    #[ORM\Column]
    private ?bool $habilitado = null;

    #[ORM\ManyToOne(inversedBy: 'lenguajes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plataforma $plataforma = null;

    /**
     * @var Collection<int, TraduccionPlataforma>
     */
    #[ORM\OneToMany(targetEntity: TraduccionPlataforma::class, mappedBy: 'lenguaje', orphanRemoval: true)]
    private Collection $traducciones_plataforma;

    #[ORM\ManyToOne(inversedBy: 'lenguajes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Moneda $moneda_def = null;

    /**
     * @var Collection<int, TraduccionBooking>
     */
    #[ORM\OneToMany(targetEntity: TraduccionBooking::class, mappedBy: 'lenguaje', orphanRemoval: true)]
    private Collection $traduccionesBooking;

    public function __construct(Plataforma $plataforma, string $nombre, string $codigo)
    {
        $this->plataforma = $plataforma;
        $this->nombre = $nombre;
        $this->codigo = $codigo;
        $this->habilitado = false;
        $this->traducciones_plataforma = new ArrayCollection();
        $this->traduccionesBooking = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
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

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function setIcono(string $icono): static
    {
        $this->icono = $icono;

        return $this;
    }

    public function isHabilitado(): ?bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): static
    {
        $this->habilitado = $habilitado;

        return $this;
    }

    public function getPlataforma(): ?Plataforma
    {
        return $this->plataforma;
    }

    public function setPlataforma(?Plataforma $plataforma): static
    {
        $this->plataforma = $plataforma;

        return $this;
    }

    /**
     * @return Collection<int, TraduccionPlataforma>
     */
    public function getTraduccionesPlataforma(): Collection
    {
        return $this->traducciones_plataforma;
    }

    public function addTraduccionesPlataforma(TraduccionPlataforma $traduccionesPlataforma): static
    {
        if (!$this->traducciones_plataforma->contains($traduccionesPlataforma)) {
            $this->traducciones_plataforma->add($traduccionesPlataforma);
            $traduccionesPlataforma->setLenguaje($this);
        }

        return $this;
    }

    public function removeTraduccionesPlataforma(TraduccionPlataforma $traduccionesPlataforma): static
    {
        if ($this->traducciones_plataforma->removeElement($traduccionesPlataforma)) {
            // set the owning side to null (unless already changed)
            if ($traduccionesPlataforma->getLenguaje() === $this) {
                $traduccionesPlataforma->setLenguaje(null);
            }
        }

        return $this;
    }

    public function getMonedaDef(): ?Moneda
    {
        return $this->moneda_def;
    }

    public function setMonedaDef(?Moneda $moneda_def): static
    {
        $this->moneda_def = $moneda_def;

        return $this;
    }

    /**
     * @return Collection<int, TraduccionBooking>
     */
    public function getTraduccionesBooking(): Collection
    {
        return $this->traduccionesBooking;
    }

    public function addTraduccionesBooking(TraduccionBooking $traduccionesBooking): static
    {
        if (!$this->traduccionesBooking->contains($traduccionesBooking)) {
            $this->traduccionesBooking->add($traduccionesBooking);
            $traduccionesBooking->setLenguaje($this);
        }

        return $this;
    }

    public function removeTraduccionesBooking(TraduccionBooking $traduccionesBooking): static
    {
        if ($this->traduccionesBooking->removeElement($traduccionesBooking)) {
            // set the owning side to null (unless already changed)
            if ($traduccionesBooking->getLenguaje() === $this) {
                $traduccionesBooking->setLenguaje(null);
            }
        }

        return $this;
    }
}
