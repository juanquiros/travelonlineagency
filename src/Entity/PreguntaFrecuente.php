<?php

namespace App\Entity;

use App\Repository\PreguntaFrecuenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreguntaFrecuenteRepository::class)]
class PreguntaFrecuente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $respuesta = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lenguaje $lenguajeDefecto = null;

    /**
     * @var Collection<int, TraduccionPreguntaFrecuente>
     */
    #[ORM\OneToMany(targetEntity: TraduccionPreguntaFrecuente::class, mappedBy: 'preguntaFrecuente', orphanRemoval: true)]
    private Collection $traduccionesPreguntaFrecuente;

    public function __construct()
    {
        $this->traduccionesPreguntaFrecuente = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getRespuesta(): ?string
    {
        return $this->respuesta;
    }

    public function setRespuesta(string $respuesta): static
    {
        $this->respuesta = $respuesta;

        return $this;
    }

    public function getLenguajeDefecto(): ?Lenguaje
    {
        return $this->lenguajeDefecto;
    }

    public function setLenguajeDefecto(?Lenguaje $lenguajeDefecto): static
    {
        $this->lenguajeDefecto = $lenguajeDefecto;

        return $this;
    }

    public function getTraduccion(Lenguaje $lenguaje) :TraduccionPreguntaFrecuente
    {
        $traduccion=null;
        if($this->lenguajeDefecto->getId() != $lenguaje->getId()){
            foreach ($this->traduccionesPreguntaFrecuente as $item){
                if($item->getLenguaje() === $lenguaje)$traduccion = $item;
            }
        }
        if(!isset($traduccion) || empty($traduccion) || $this->lenguajeDefecto->getId() == $lenguaje->getId() ){
            $traduccion = new TraduccionPreguntaFrecuente();

            $traduccion->setLenguaje($lenguaje);
            $traduccion->setPreguntaFrecuente($this);
            $traduccion->setRespuesta($this->respuesta);
            $traduccion->setTitulo($this->titulo);

        }
        return $traduccion;
    }
    public function getTraduccionSiExiste(Lenguaje $lenguaje) :?TraduccionPreguntaFrecuente
    {
        $traduccion=null;
        if($this->lenguajeDefecto->getId() != $lenguaje->getId()){
            foreach ($this->traduccionesPreguntaFrecuente as $item){
                if($item->getLenguaje() === $lenguaje)$traduccion = $item;
            }
        }
        if($this->lenguajeDefecto->getId() == $lenguaje->getId() ){
            $traduccion = new TraduccionPreguntaFrecuente();

            $traduccion->setLenguaje($lenguaje);
            $traduccion->setPreguntaFrecuente($this);
            $traduccion->setRespuesta($this->respuesta);
            $traduccion->setTitulo($this->titulo);

        }
        return $traduccion;
    }


    /**
     * @return Collection<int, TraduccionPreguntaFrecuente>
     */
    public function getTraduccionesPreguntaFrecuente(): Collection
    {
        return $this->traduccionesPreguntaFrecuente;
    }

    public function addTraduccionesPreguntaFrecuente(TraduccionPreguntaFrecuente $traduccionesPreguntaFrecuente): static
    {
        if (!$this->traduccionesPreguntaFrecuente->contains($traduccionesPreguntaFrecuente)) {
            $this->traduccionesPreguntaFrecuente->add($traduccionesPreguntaFrecuente);
            $traduccionesPreguntaFrecuente->setPreguntaFrecuente($this);
        }

        return $this;
    }

    public function removeTraduccionesPreguntaFrecuente(TraduccionPreguntaFrecuente $traduccionesPreguntaFrecuente): static
    {
        if ($this->traduccionesPreguntaFrecuente->removeElement($traduccionesPreguntaFrecuente)) {
            // set the owning side to null (unless already changed)
            if ($traduccionesPreguntaFrecuente->getPreguntaFrecuente() === $this) {
                $traduccionesPreguntaFrecuente->setPreguntaFrecuente(null);
            }
        }

        return $this;
    }

    public function getLenguajesDisp():array
    {

        $lenguajes = [];
        if(isset($this->lenguajeDefecto) && !empty($this->lenguajeDefecto)){
            $lenguajes[ $this->lenguajeDefecto->getCodigo()]= $this->lenguajeDefecto;
        }
        foreach ($this->getTraduccionesPreguntaFrecuente() as $trad){
            $lenguajes[$trad->getLenguaje()->getCodigo()]=$trad->getLenguaje();
        }
        return $lenguajes;
    }
}
