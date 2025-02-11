<?php

namespace App\Entity;

use App\Repository\TraduccionPreguntaFrecuenteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraduccionPreguntaFrecuenteRepository::class)]
class TraduccionPreguntaFrecuente
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
    private ?Lenguaje $lenguaje = null;

    #[ORM\ManyToOne(inversedBy: 'traduccionesPreguntaFrecuente')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PreguntaFrecuente $preguntaFrecuente = null;

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

    public function getLenguaje(): ?Lenguaje
    {
        return $this->lenguaje;
    }

    public function setLenguaje(?Lenguaje $lenguaje): static
    {
        $this->lenguaje = $lenguaje;

        return $this;
    }

    public function getPreguntaFrecuente(): ?PreguntaFrecuente
    {
        return $this->preguntaFrecuente;
    }

    public function setPreguntaFrecuente(?PreguntaFrecuente $preguntaFrecuente): static
    {
        $this->preguntaFrecuente = $preguntaFrecuente;

        return $this;
    }
}
