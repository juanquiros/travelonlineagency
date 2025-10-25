<?php

namespace App\Entity;

use App\Entity\BootstrapIcon;
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

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'bootstrap_icon_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BootstrapIcon $iconDefinition = null;

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
        return $this->getIconMarkup();
    }

    public function setIcono(?string $icono): self
    {
        $this->icono = $icono;
        if (null === $icono) {
            $this->iconDefinition = null;
        }

        return $this;
    }

    public function getIconDefinition(): ?BootstrapIcon
    {
        return $this->iconDefinition;
    }

    public function setIconDefinition(?BootstrapIcon $iconDefinition): self
    {
        $this->iconDefinition = $iconDefinition;
        if ($iconDefinition instanceof BootstrapIcon) {
            $this->icono = $iconDefinition->getMarkup();
        }

        return $this;
    }

    public function getIconMarkup(): ?string
    {
        if ($this->iconDefinition instanceof BootstrapIcon) {
            return $this->iconDefinition->getMarkup();
        }

        return $this->icono;
    }

    public function getIconCssClass(): ?string
    {
        if ($this->iconDefinition instanceof BootstrapIcon) {
            return $this->iconDefinition->getCssClass();
        }

        if (!is_string($this->icono) || $this->icono === '') {
            return null;
        }

        if (preg_match('/bi-([a-z0-9-]+)/i', $this->icono, $matches)) {
            return 'bi-' . $matches[1];
        }

        return null;
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
