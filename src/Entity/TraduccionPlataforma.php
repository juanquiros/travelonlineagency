<?php

namespace App\Entity;

use App\Repository\TraduccionPlataformaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraduccionPlataformaRepository::class)]
class TraduccionPlataforma
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $key_name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'traducciones_plataforma')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lenguaje $lenguaje = null;

    #[ORM\ManyToOne(inversedBy: 'traducciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plataforma $plataforma = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyName(): ?string
    {
        return $this->key_name;
    }

    public function setKeyName(string $key_name): static
    {
        $this->key_name = $key_name;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

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

    public function getPlataforma(): ?Plataforma
    {
        return $this->plataforma;
    }

    public function setPlataforma(?Plataforma $plataforma): static
    {
        $this->plataforma = $plataforma;

        return $this;
    }
}
