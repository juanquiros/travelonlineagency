<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(length: 90, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detalles = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $form_requerido = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imagenes = null;

    #[ORM\Column]
    private ?int $disponibles = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validoHasta = null;

    #[ORM\Column]
    private ?bool $habilitado = null;

    /**
     * @var Collection<int, Precio>
     */
    #[ORM\OneToMany(targetEntity: Precio::class, mappedBy: 'booking')]
    private Collection $precios;

    /**
     * @var Collection<int, TraduccionBooking>
     */
    #[ORM\OneToMany(targetEntity: TraduccionBooking::class, mappedBy: 'booking', orphanRemoval: true)]
    private Collection $traducciones;

    #[ORM\ManyToOne]
    private ?Lenguaje $lenguaje = null;

    /**
     * @var Collection<int, SolicitudReserva>
     */
    #[ORM\OneToMany(targetEntity: SolicitudReserva::class, mappedBy: 'Booking', orphanRemoval: true)]
    private Collection $solicitudesReserva;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fechasdelservicio = null;

    /**
     * @param bool|null $habilitado
     */
    public function __construct()
    {
        $this->disponibles = 0;
        $this->habilitado = false;
        $this->precios = new ArrayCollection();
        $this->traducciones = new ArrayCollection();
        $this->solicitudesReserva = new ArrayCollection();
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

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getDetalles(): ?string
    {
        return $this->detalles;
    }

    public function setDetalles(?string $detalles): static
    {
        $this->detalles = $detalles;

        return $this;
    }

    public function getFormRequerido(): ?string
    {
        return $this->form_requerido;
    }

    public function setFormRequerido(?string $form_requerido): static
    {
        $this->form_requerido = $form_requerido;

        return $this;
    }

    public function getImagenes(): ?string
    {
        return $this->imagenes;
    }

    public function setImagenes(?string $imagenes): static
    {
        $this->imagenes = $imagenes;

        return $this;
    }

    public function getDisponibles(): ?int
    {
        return $this->disponibles;
    }

    public function setDisponibles(int $disponibles = 0): static
    {
        if($disponibles < 0) $disponibles = 0;
        $this->disponibles = $disponibles;

        return $this;
    }

    public function getValidoHasta(): ?\DateTimeInterface
    {
        return $this->validoHasta;
    }

    public function setValidoHasta(?\DateTimeInterface $validoHasta): static
    {
        $this->validoHasta = $validoHasta;

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
            $precio->setBooking($this);
        }

        return $this;
    }

    public function removePrecio(Precio $precio): static
    {
        if ($this->precios->removeElement($precio)) {
            // set the owning side to null (unless already changed)
            if ($precio->getBooking() === $this) {
                $precio->setBooking(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TraduccionBooking>
     */
    public function getTraducciones(): Collection
    {
        return $this->traducciones;
    }

    public function addTraduccione(TraduccionBooking $traduccione): static
    {
        if (!$this->traducciones->contains($traduccione)) {
            $this->traducciones->add($traduccione);
            $traduccione->setBooking($this);
        }

        return $this;
    }

    public function removeTraduccione(TraduccionBooking $traduccione): static
    {
        if ($this->traducciones->removeElement($traduccione)) {
            // set the owning side to null (unless already changed)
            if ($traduccione->getBooking() === $this) {
                $traduccione->setBooking(null);
            }
        }

        return $this;
    }

    public function getLenguaje(): ?Lenguaje
    {
        return $this->lenguaje;
    }

    public function setLenguaje(?Lenguaje $lenguaje): static
    {
        $this->lenguaje = $lenguaje;

        return $this;
    }

    /**
     * @return Collection<int, SolicitudReserva>
     */
    public function getSolicitudesReserva(): Collection
    {
        return $this->solicitudesReserva;
    }

    public function addSolicitudesReserva(SolicitudReserva $solicitudesReserva): static
    {
        if (!$this->solicitudesReserva->contains($solicitudesReserva)) {
            $this->solicitudesReserva->add($solicitudesReserva);
            $solicitudesReserva->setBooking($this);
        }

        return $this;
    }

    public function removeSolicitudesReserva(SolicitudReserva $solicitudesReserva): static
    {
        if ($this->solicitudesReserva->removeElement($solicitudesReserva)) {
            // set the owning side to null (unless already changed)
            if ($solicitudesReserva->getBooking() === $this) {
                $solicitudesReserva->setBooking(null);
            }
        }

        return $this;
    }
    public function getImgPortada():?string
    {
        $img = json_decode($this->imagenes);
        $portada = null;
        if(isset($img) && !empty($img)) {
            foreach ($img as $imagen) {
                if($imagen->portada)$portada= $imagen->imagen;
            }
        }
        return $portada;
    }

    public function getFechasdelservicio(): ?string
    {
        return $this->fechasdelservicio;
    }
    public function getFechasdelservicioArray(): ?array
    {
        return json_decode($this->fechasdelservicio);
    }

    public function setFechasdelservicio(?string $fechasdelservicio): static
    {
        $this->fechasdelservicio = $fechasdelservicio;

        return $this;
    }
}
