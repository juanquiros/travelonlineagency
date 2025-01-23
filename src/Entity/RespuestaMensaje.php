<?php

namespace App\Entity;

use App\Repository\RespuestaMensajeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RespuestaMensajeRepository::class)]
class RespuestaMensaje
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'respuestasMensaje')]
    private ?Mensaje $mensajeContacto = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $mensaje = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuario $usuario = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMensajeContacto(): ?Mensaje
    {
        return $this->mensajeContacto;
    }

    public function setMensajeContacto(?Mensaje $mensajeContacto): static
    {
        $this->mensajeContacto = $mensajeContacto;

        return $this;
    }

    public function getMensaje(): ?string
    {
        return $this->mensaje;
    }

    public function setMensaje(string $mensaje): static
    {
        $this->mensaje = $mensaje;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }
}
