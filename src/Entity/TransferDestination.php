<?php

namespace App\Entity;

use App\Repository\TransferDestinationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferDestinationRepository::class)]
#[ORM\Table(name: 'transfer_destination')]
class TransferDestination
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nombre = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $coordenadasLat = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $coordenadasLng = null;

    #[ORM\ManyToOne(inversedBy: 'destinos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TransferDestinationCategory $categoria = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcionCorta = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'descripcion')]
    private ?string $descripcionDetallada = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $tarifaBase = '0.00';

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column(length: 255, nullable: true, name: 'imagen_portada')]
    private ?string $imagenPrincipal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $x = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whatsapp = null;

    #[ORM\Column(length: 255, nullable: true, name: 'sitio_web')]
    private ?string $sitioWeb = null;

    /**
     * @var Collection<int, TransferComboDestination>
     */
    #[ORM\OneToMany(mappedBy: 'destino', targetEntity: TransferComboDestination::class, orphanRemoval: true)]
    private Collection $combos;

    /**
     * @var Collection<int, TransferRequestDestination>
     */
    #[ORM\OneToMany(mappedBy: 'destino', targetEntity: TransferRequestDestination::class, orphanRemoval: true)]
    private Collection $solicitudes;

    public function __construct()
    {
        $this->combos = new ArrayCollection();
        $this->solicitudes = new ArrayCollection();
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

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(?string $direccion): self
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

    public function getCategoria(): ?TransferDestinationCategory
    {
        return $this->categoria;
    }

    public function setCategoria(?TransferDestinationCategory $categoria): self
    {
        $this->categoria = $categoria;

        return $this;
    }

    public function getDescripcionCorta(): ?string
    {
        return $this->descripcionCorta;
    }

    public function setDescripcionCorta(?string $descripcionCorta): self
    {
        $this->descripcionCorta = $descripcionCorta;

        return $this;
    }

    public function getDescripcionDetallada(): ?string
    {
        return $this->descripcionDetallada;
    }

    public function setDescripcionDetallada(?string $descripcionDetallada): self
    {
        $this->descripcionDetallada = $descripcionDetallada;

        return $this;
    }

    public function getTarifaBase(): string
    {
        return $this->tarifaBase;
    }

    public function setTarifaBase(string $tarifaBase): self
    {
        $this->tarifaBase = $tarifaBase;

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

    public function getImagenPrincipal(): ?string
    {
        return $this->imagenPrincipal;
    }

    public function setImagenPrincipal(?string $imagenPrincipal): self
    {
        $this->imagenPrincipal = $imagenPrincipal;

        return $this;
    }

    public function getImagenPortada(): ?string
    {
        return $this->imagenPrincipal;
    }

    public function setImagenPortada(?string $imagenPortada): self
    {
        return $this->setImagenPrincipal($imagenPortada);
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getX(): ?string
    {
        return $this->x;
    }

    public function setX(?string $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): self
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function getWhatsapp(): ?string
    {
        return $this->whatsapp;
    }

    public function setWhatsapp(?string $whatsapp): self
    {
        $this->whatsapp = $whatsapp;

        return $this;
    }

    public function getSitioWeb(): ?string
    {
        return $this->sitioWeb;
    }

    public function setSitioWeb(?string $sitioWeb): self
    {
        $this->sitioWeb = $sitioWeb;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->getDescripcionDetallada();
    }

    public function setDescripcion(?string $descripcion): self
    {
        return $this->setDescripcionDetallada($descripcion);
    }

    public function getLatitude(): ?float
    {
        return $this->getCoordenadasLat();
    }

    public function getLongitude(): ?float
    {
        return $this->getCoordenadasLng();
    }

    public function withLocation(?float $lat, ?float $lng): self
    {
        $this->coordenadasLat = $lat;
        $this->coordenadasLng = $lng;

        return $this;
    }

    /**
     * @return Collection<int, TransferComboDestination>
     */
    public function getCombos(): Collection
    {
        return $this->combos;
    }

    public function addCombo(TransferComboDestination $combo): self
    {
        if (!$this->combos->contains($combo)) {
            $this->combos->add($combo);
            $combo->setDestino($this);
        }

        return $this;
    }

    public function removeCombo(TransferComboDestination $combo): self
    {
        if ($this->combos->removeElement($combo)) {
            if ($combo->getDestino() === $this) {
                $combo->setDestino(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TransferRequestDestination>
     */
    public function getSolicitudes(): Collection
    {
        return $this->solicitudes;
    }

    public function addSolicitud(TransferRequestDestination $solicitud): self
    {
        if (!$this->solicitudes->contains($solicitud)) {
            $this->solicitudes->add($solicitud);
            $solicitud->setDestino($this);
        }

        return $this;
    }

    public function removeSolicitud(TransferRequestDestination $solicitud): self
    {
        if ($this->solicitudes->removeElement($solicitud)) {
            if ($solicitud->getDestino() === $this) {
                $solicitud->setDestino(null);
            }
        }

        return $this;
    }
}
