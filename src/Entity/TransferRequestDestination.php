<?php

namespace App\Entity;

use App\Repository\TransferRequestDestinationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferRequestDestinationRepository::class)]
#[ORM\Table(name: 'transfer_request_destination')]
class TransferRequestDestination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'destinos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferRequest $solicitud = null;

    #[ORM\ManyToOne(inversedBy: 'solicitudes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferDestination $destino = null;

    #[ORM\Column]
    private int $posicion = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSolicitud(): ?TransferRequest
    {
        return $this->solicitud;
    }

    public function setSolicitud(?TransferRequest $solicitud): self
    {
        $this->solicitud = $solicitud;

        return $this;
    }

    public function getDestino(): ?TransferDestination
    {
        return $this->destino;
    }

    public function setDestino(?TransferDestination $destino): self
    {
        $this->destino = $destino;

        return $this;
    }

    public function getPosicion(): int
    {
        return $this->posicion;
    }

    public function setPosicion(int $posicion): self
    {
        $this->posicion = $posicion;

        return $this;
    }
}
