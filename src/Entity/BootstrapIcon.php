<?php

namespace App\Entity;

use App\Repository\BootstrapIconRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BootstrapIconRepository::class)]
#[ORM\Table(name: 'bootstrap_icon')]
class BootstrapIcon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $nombre = '';

    #[ORM\Column(name: 'css_class', length: 120, unique: true)]
    private string $cssClass = '';

    /**
     * @var Collection<int, TransferDestinationCategory>
     */
    #[ORM\OneToMany(mappedBy: 'iconDefinition', targetEntity: TransferDestinationCategory::class)]
    private Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nombre;
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

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->cssClass = trim($cssClass);

        return $this;
    }

    public function getMarkup(): string
    {
        $class = trim($this->cssClass);
        if ($class === '') {
            $class = 'bi-geo-alt';
        }

        if (!str_contains($class, 'bi-')) {
            $class = 'bi-' . ltrim($class, '-');
        }

        return sprintf('<span class="bi %s"></span>', $class);
    }

    /**
     * @return Collection<int, TransferDestinationCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }
}
