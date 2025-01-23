<?php

namespace App\Entity;

use App\Repository\EstadoReservaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EstadoReservaRepository::class)]
class EstadoReserva
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, TraduccionEstado>
     */
    #[ORM\OneToMany(targetEntity: TraduccionEstado::class, mappedBy: 'estadoReserva', orphanRemoval: true)]
    private Collection $traduccionesEstado;

    /**
     * @var Collection<int, SolicitudReserva>
     */
    #[ORM\OneToMany(targetEntity: SolicitudReserva::class, mappedBy: 'estado')]
    private Collection $reservas;

    public function __construct()
    {
        $this->traduccionesEstado = new ArrayCollection();
        $this->reservas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, TraduccionEstado>
     */
    public function getTraduccionesEstado(): Collection
    {
        return $this->traduccionesEstado;
    }

    public function addTraduccionesEstado(TraduccionEstado $traduccionesEstado): static
    {
        if (!$this->traduccionesEstado->contains($traduccionesEstado)) {
            $this->traduccionesEstado->add($traduccionesEstado);
            $traduccionesEstado->setEstadoReserva($this);
        }

        return $this;
    }

    public function removeTraduccionesEstado(TraduccionEstado $traduccionesEstado): static
    {
        if ($this->traduccionesEstado->removeElement($traduccionesEstado)) {
            // set the owning side to null (unless already changed)
            if ($traduccionesEstado->getEstadoReserva() === $this) {
                $traduccionesEstado->setEstadoReserva(null);
            }
        }

        return $this;
    }

    public function getnombreporlenguaje(string $codLenguaje) :?TraduccionEstado
    {
        $traduccion=null;
        foreach ($this->traduccionesEstado as $item){
            if(strtolower($item->getLenguaje()->getCodigo()) === strtolower($codLenguaje))$traduccion = $item;
        }
        return $traduccion;
    }

    /**
     * @return Collection<int, SolicitudReserva>
     */
    public function getReservas(): Collection
    {
        return $this->reservas;
    }

    public function addReserva(SolicitudReserva $reserva): static
    {
        if (!$this->reservas->contains($reserva)) {
            $this->reservas->add($reserva);
            $reserva->setEstado($this);
        }

        return $this;
    }

    public function removeReserva(SolicitudReserva $reserva): static
    {
        if ($this->reservas->removeElement($reserva)) {
            // set the owning side to null (unless already changed)
            if ($reserva->getEstado() === $this) {
                $reserva->setEstado(null);
            }
        }

        return $this;
    }
}
