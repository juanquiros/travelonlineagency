<?php

namespace App\Entity;

use App\Repository\DestinoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DestinoRepository::class)]
#[ORM\Table(name: 'destino')]
class Destino
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $nombre = '';

    #[ORM\Column(length: 255)]
    private string $direccion = '';

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $coordenadasLat = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $coordenadasLng = null;

    #[ORM\Column(length: 255)]
    private string $descripcionCorta = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $descripcionDetallada = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagenPrincipal = null;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\ManyToOne(inversedBy: 'destinos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DestinoCategoria $categoria = null;

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

    public function getDireccion(): string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): self
    {
        $this->direccion = $direccion;

        return $this;
    }

    public function getCoordenadasLat(): ?float
    {
        return $this->coordenadasLat;
    }

    public function setCoordenadasLat(?float $coordenadasLat): self
    {
        $this->coordenadasLat = $coordenadasLat;

        return $this;
    }

    public function getCoordenadasLng(): ?float
    {
        return $this->coordenadasLng;
    }

    public function setCoordenadasLng(?float $coordenadasLng): self
    {
        $this->coordenadasLng = $coordenadasLng;

        return $this;
    }

    public function getDescripcionCorta(): string
    {
        return $this->descripcionCorta;
    }

    public function setDescripcionCorta(string $descripcionCorta): self
    {
        $this->descripcionCorta = $descripcionCorta;

        return $this;
    }

    public function getDescripcionDetallada(): string
    {
        return $this->descripcionDetallada;
    }

    public function setDescripcionDetallada(string $descripcionDetallada): self
    {
        $this->descripcionDetallada = $descripcionDetallada;

        return $this;
    }

    public function getImagenPrincipal(): ?string
    {
        return $this->imagenPrincipal;
    }

    public function setImagenPrincipal(?string $imagenPrincipal): self
    {
        $this->imagenPrincipal = $imagenPrincipal;

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

    public function getCategoria(): ?DestinoCategoria
    {
        return $this->categoria;
    }

    public function setCategoria(?DestinoCategoria $categoria): self
    {
        $this->categoria = $categoria;

        return $this;
    }
}
