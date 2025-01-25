<?php

namespace App\Entity;

use App\Repository\PlataformaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlataformaRepository::class)]
class Plataforma
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column]
    private ?bool $traslados_OD_libres = null;

    #[ORM\Column]
    private ?float $tasa_traslados_def = null;

    /**
     * @var Collection<int, Lenguaje>
     */
    #[ORM\OneToMany(targetEntity: Lenguaje::class, mappedBy: 'plataforma', orphanRemoval: true)]
    private Collection $lenguajes;

    /**
     * @var Collection<int, TraduccionPlataforma>
     */
    #[ORM\OneToMany(targetEntity: TraduccionPlataforma::class, mappedBy: 'plataforma', orphanRemoval: true)]
    private Collection $traducciones;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lenguaje $language_def = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Moneda $moneda_def = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?CredencialesPayPal $CredencialesPayPal = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?CredencialesMercadoPago $credencialesMercadoPago = null;

    public function __construct()
    {
        $this->traslados_OD_libres = false;
        $this->lenguajes = new ArrayCollection();
        $this->traducciones = new ArrayCollection();
    }

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

    public function isTrasladosODLibres(): ?bool
    {
        return $this->traslados_OD_libres;
    }

    public function setTrasladosODLibres(bool $traslados_OD_libres): static
    {
        $this->traslados_OD_libres = $traslados_OD_libres;

        return $this;
    }

    public function getTasaTrasladosDef(): ?float
    {
        return $this->tasa_traslados_def;
    }

    public function setTasaTrasladosDef(float $tasa_traslados_def): static
    {
        $this->tasa_traslados_def = $tasa_traslados_def;

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
            $lenguaje->setPlataforma($this);
        }

        return $this;
    }

    public function removeLenguaje(Lenguaje $lenguaje): static
    {
        if ($this->lenguajes->removeElement($lenguaje)) {
            // set the owning side to null (unless already changed)
            if ($lenguaje->getPlataforma() === $this) {
                $lenguaje->setPlataforma(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TraduccionPlataforma>
     */
    public function getTraducciones(): Collection
    {
        return $this->traducciones;
    }

    public function addTraduccione(TraduccionPlataforma $traduccione): static
    {
        if (!$this->traducciones->contains($traduccione)) {
            $this->traducciones->add($traduccione);
            $traduccione->setPlataforma($this);
        }

        return $this;
    }

    public function removeTraduccione(TraduccionPlataforma $traduccione): static
    {
        if ($this->traducciones->removeElement($traduccione)) {
            // set the owning side to null (unless already changed)
            if ($traduccione->getPlataforma() === $this) {
                $traduccione->setPlataforma(null);
            }
        }

        return $this;
    }

    public function getLanguageDef(): ?Lenguaje
    {
        return $this->language_def;
    }

    public function setLanguageDef(?Lenguaje $language_def): static
    {
        $this->language_def = $language_def;

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

    public function getCredencialesPayPal(): ?CredencialesPayPal
    {
        return $this->CredencialesPayPal;
    }

    public function setCredencialesPayPal(?CredencialesPayPal $CredencialesPayPal): static
    {
        $this->CredencialesPayPal = $CredencialesPayPal;

        return $this;
    }

    public function getCredencialesMercadoPago(): ?CredencialesMercadoPago
    {
        return $this->credencialesMercadoPago;
    }

    public function setCredencialesMercadoPago(?CredencialesMercadoPago $credencialesMercadoPago): static
    {
        $this->credencialesMercadoPago = $credencialesMercadoPago;

        return $this;
    }
}
