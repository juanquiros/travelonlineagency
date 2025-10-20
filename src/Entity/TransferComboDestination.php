<?php

namespace App\Entity;

use App\Repository\TransferComboDestinationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferComboDestinationRepository::class)]
#[ORM\Table(name: 'transfer_combo_destination')]
class TransferComboDestination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'destinos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferCombo $combo = null;

    #[ORM\ManyToOne(inversedBy: 'combos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferDestination $destino = null;

    #[ORM\Column]
    private int $posicion = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCombo(): ?TransferCombo
    {
        return $this->combo;
    }

    public function setCombo(?TransferCombo $combo): self
    {
        $this->combo = $combo;

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
