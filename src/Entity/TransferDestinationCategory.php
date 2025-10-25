<?php

namespace App\Entity;

use App\Repository\TransferDestinationCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferDestinationCategoryRepository::class)]
#[ORM\Table(name: 'transfer_destination_category')]
class TransferDestinationCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $nombre = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icono = null;

    #[ORM\Column(length: 9, nullable: true)]
    private ?string $color = null;

    /**
     * @var Collection<int, TransferDestination>
     */
    #[ORM\OneToMany(mappedBy: 'categoria', targetEntity: TransferDestination::class)]
    private Collection $destinos;

    public function __construct()
    {
        $this->destinos = new ArrayCollection();
    }

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

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function setIcono(?string $icono): self
    {
        $this->icono = $icono;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        if (null === $color) {
            $this->color = null;

            return $this;
        }

        $normalized = strtoupper(trim($color));
        if ('' === $normalized) {
            $this->color = null;

            return $this;
        }
        $normalized = '#' . ltrim($normalized, '#');
        $this->color = $normalized;

        return $this;
    }

    /**
     * @return Collection<int, TransferDestination>
     */
    public function getDestinos(): Collection
    {
        return $this->destinos;
    }
}
