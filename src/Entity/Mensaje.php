<?php

namespace App\Entity;

use App\Repository\MensajeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MensajeRepository::class)]
class Mensaje
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $mensaje = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\Column(length: 255)]
    private ?string $session_id = null;

    /**
     * @var Collection<int, RespuestaMensaje>
     */
    #[ORM\OneToMany(targetEntity: RespuestaMensaje::class, mappedBy: 'mensajeContacto')]
    private Collection $respuestasMensaje;

    public function __construct()
    {
        $this->respuestasMensaje = new ArrayCollection();
        $this->created_at=new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return ucwords($this->nombre);
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = ucwords($nombre);

        return $this;
    }

    public function getEmail(): ?string
    {
        return strtolower($this->email);
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower($email);

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

    public function getSessionId(): ?string
    {
        return $this->session_id;
    }

    public function setSessionId(string $session_id): static
    {
        $this->session_id = $session_id;

        return $this;
    }

    /**
     * @return Collection<int, RespuestaMensaje>
     */
    public function getRespuestasMensaje(): Collection
    {
        return $this->respuestasMensaje;
    }

    public function addRespuestasMensaje(RespuestaMensaje $respuestasMensaje): static
    {
        if (!$this->respuestasMensaje->contains($respuestasMensaje)) {
            $this->respuestasMensaje->add($respuestasMensaje);
            $respuestasMensaje->setMensaje($this);
        }

        return $this;
    }

    public function removeRespuestasMensaje(RespuestaMensaje $respuestasMensaje): static
    {
        if ($this->respuestasMensaje->removeElement($respuestasMensaje)) {
            // set the owning side to null (unless already changed)
            if ($respuestasMensaje->getMensaje() === $this) {
                $respuestasMensaje->setMensaje(null);
            }
        }

        return $this;
    }
}
