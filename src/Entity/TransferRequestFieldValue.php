<?php

namespace App\Entity;

use App\Repository\TransferRequestFieldValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferRequestFieldValueRepository::class)]
#[ORM\Table(name: 'transfer_request_field_value')]
class TransferRequestFieldValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'valores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferRequest $solicitud = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferFormField $campo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $valor = null;

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

    public function getCampo(): ?TransferFormField
    {
        return $this->campo;
    }

    public function setCampo(?TransferFormField $campo): self
    {
        $this->campo = $campo;

        return $this;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(?string $valor): self
    {
        $this->valor = $valor;

        return $this;
    }
}
