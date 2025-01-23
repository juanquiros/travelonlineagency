<?php

namespace App\Entity;

use App\Repository\servicioReservaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: servicioReservaRepository::class)]
class servicioReserva
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(length: 255)]
    private ?string $b_descripcion = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $detalle = null;

    #[ORM\Column(length: 255)]
    private ?string $img = null;

    #[ORM\Column]
    private ?bool $habilitado = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $precios = [];



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

    public function getBDescripcion(): ?string
    {
        return $this->b_descripcion;
    }

    public function setBDescripcion(string $b_descripcion): static
    {
        $this->b_descripcion = $b_descripcion;

        return $this;
    }

    public function getDetalle(): ?string
    {
        return $this->detalle;
    }

    public function setDetalle(string $detalle): static
    {
        $this->detalle = $detalle;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(string $img): static
    {
        $this->img = $img;

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

    public function getPrecios(): array
    {
        return $this->precios;
    }

    public function setPrecios(array $precios): static
    {
        $this->precios = $precios;

        return $this;
    }

}
