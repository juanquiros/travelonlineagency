<?php

namespace App\Entity;

use App\Repository\PayPalPagoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayPalPagoRepository::class)]
class PayPalPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ordersId = null;

    #[ORM\ManyToOne(inversedBy: 'pagosPayPal')]
    private ?SolicitudReserva $solicitudReserva = null;

    #[ORM\ManyToOne]
    private ?TransferRequest $transferRequest = null;

    #[ORM\ManyToOne(inversedBy: 'pagos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CredencialesPayPal $credencialesPayPal = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $estado = null;


    #[ORM\Column]
    private ?float $total = null;

    /**
     * @var Collection<int, DetallePagoPayPal>
     */
    #[ORM\OneToMany(targetEntity: DetallePagoPayPal::class, mappedBy: 'PayPalPago', orphanRemoval: true)]
    private Collection $detallesPago;



    public function __construct()
    {
        $this->updatedAt = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->detallesPago = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdersId(): ?string
    {
        return $this->ordersId;
    }

    public function setOrdersId(string $ordersId): static
    {
        $this->ordersId = $ordersId;

        return $this;
    }

    public function getSolicitudReserva(): ?SolicitudReserva
    {
        return $this->solicitudReserva;
    }

    public function setSolicitudReserva(?SolicitudReserva $solicitudReserva): static
    {
        $this->solicitudReserva = $solicitudReserva;

        return $this;
    }

    public function getTransferRequest(): ?TransferRequest
    {
        return $this->transferRequest;
    }

    public function setTransferRequest(?TransferRequest $transferRequest): static
    {
        $this->transferRequest = $transferRequest;

        return $this;
    }

    public function getCredencialesPayPal(): ?CredencialesPayPal
    {
        return $this->credencialesPayPal;
    }

    public function setCredencialesPayPal(?CredencialesPayPal $credencialesPayPal): static
    {
        $this->credencialesPayPal = $credencialesPayPal;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }



    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return Collection<int, DetallePagoPayPal>
     */
    public function getDetallesPago(): Collection
    {
        return $this->detallesPago;
    }

    public function addDetallesPago(DetallePagoPayPal $detallesPago): static
    {
        if (!$this->detallesPago->contains($detallesPago)) {
            $this->detallesPago->add($detallesPago);
            $detallesPago->setPayPalPago($this);
        }

        return $this;
    }

    public function removeDetallesPago(DetallePagoPayPal $detallesPago): static
    {
        if ($this->detallesPago->removeElement($detallesPago)) {
            // set the owning side to null (unless already changed)
            if ($detallesPago->getPayPalPago() === $this) {
                $detallesPago->setPayPalPago(null);
            }
        }

        return $this;
    }


}
