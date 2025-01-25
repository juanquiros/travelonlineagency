<?php

namespace App\Entity;

use App\Repository\SolicitudReservaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolicitudReservaRepository::class)]
class SolicitudReserva
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private ?int $phone = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $form_required = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $inChargeOf = null;

    #[ORM\Column]
    private ?bool $canceled = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'solicitudesReserva')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $Booking = null;



    #[ORM\Column(nullable: true)]
    private ?\DateTime $fechaSeleccionada = null;

    #[ORM\ManyToOne(inversedBy: 'reservas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EstadoReserva $estado = null;

    /**
     * @var Collection<int, PayPalPago>
     */
    #[ORM\OneToMany(targetEntity: PayPalPago::class, mappedBy: 'solicitudReserva')]
    private Collection $pagosPayPal;

    /**
     * @var Collection<int, MercadoPagoPago>
     */
    #[ORM\OneToMany(targetEntity: MercadoPagoPago::class, mappedBy: 'solicitudReserva')]
    private Collection $pagosMercadoPago;

    /**
     * @param \DateTime|null $updated_at
     * @param \DateTime|null $created_at
     * @param bool|null $canceled
     * @param string|null $inChargeOf
     * @param string|null $form_required
     */
    public function __construct()
    {
        $this->updated_at = new \DateTime();
        $this->created_at = new \DateTime();
        $this->canceled = false;
        $this->inChargeOf = '[]';
        $this->form_required = '[]';
        $this->pagosPayPal = new ArrayCollection();
        $this->pagosMercadoPago = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->updated_at = new \DateTime();
        $this->name = $name;
        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->updated_at = new \DateTime();
        $this->surname = $surname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->updated_at = new \DateTime();
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): static
    {
        $this->updated_at = new \DateTime();
        $this->phone = $phone;
        return $this;
    }

    public function getFormRequired(): ?string
    {
        return $this->form_required;
    }
    public function getFormRequiredArray(): ?array
    {
        return json_decode($this->form_required);
    }

    public function setFormRequired(string $form_required): static
    {
        $this->updated_at = new \DateTime();
        $this->form_required = $form_required;
        return $this;
    }

    public function isCanceled(): ?bool
    {
        return $this->canceled;
    }

    public function setCanceled(bool $canceled): static
    {
        $this->updated_at = new \DateTime();
        $this->canceled = $canceled;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = new \DateTime();
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getInChargeOf(): ?string
    {
        return $this->inChargeOf;
    }
    public function getInChargeOfArray(): ?array
    {
        return json_decode($this->inChargeOf);
    }
    public function setInChargeOf(string $inChargeOf): static
    {
        $this->updated_at = new \DateTime();
        $this->inChargeOf = $inChargeOf;

        return $this;
    }

    public function getBooking(): ?Booking
    {
        return $this->Booking;
    }

    public function setBooking(?Booking $Booking): static
    {
        $this->Booking = $Booking;
        $this->updated_at = new \DateTime();
        return $this;
    }

    public function getFechaSeleccionada(): ?\DateTime
    {
        return $this->fechaSeleccionada;
    }

    public function setFechaSeleccionada(?\DateTime $fechaSeleccionada): static
    {
        $this->fechaSeleccionada = $fechaSeleccionada;
        $this->updated_at = new \DateTime();
        return $this;
    }

    public function getEstado(): ?EstadoReserva
    {
        return $this->estado;
    }

    public function setEstado(?EstadoReserva $estado): static
    {
        $this->estado = $estado;
        $this->updated_at = new \DateTime();
        return $this;
    }

    /**
     * @return Collection<int, PayPalPago>
     */
    public function getPagosPayPal(): Collection
    {
        return $this->pagosPayPal;
    }

    public function addPagosPayPal(PayPalPago $pagosPayPal): static
    {
        if (!$this->pagosPayPal->contains($pagosPayPal)) {
            $this->pagosPayPal->add($pagosPayPal);
            $pagosPayPal->setSolicitudReserva($this);
        }

        return $this;
    }

    public function removePagosPayPal(PayPalPago $pagosPayPal): static
    {
        if ($this->pagosPayPal->removeElement($pagosPayPal)) {
            // set the owning side to null (unless already changed)
            if ($pagosPayPal->getSolicitudReserva() === $this) {
                $pagosPayPal->setSolicitudReserva(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MercadoPagoPago>
     */
    public function getPagosMercadoPago(): Collection
    {
        return $this->pagosMercadoPago;
    }

    public function addPagosMercadoPago(MercadoPagoPago $pagosMercadoPago): static
    {
        if (!$this->pagosMercadoPago->contains($pagosMercadoPago)) {
            $this->pagosMercadoPago->add($pagosMercadoPago);
            $pagosMercadoPago->setSolicitudReserva($this);
        }

        return $this;
    }

    public function removePagosMercadoPago(MercadoPagoPago $pagosMercadoPago): static
    {
        if ($this->pagosMercadoPago->removeElement($pagosMercadoPago)) {
            // set the owning side to null (unless already changed)
            if ($pagosMercadoPago->getSolicitudReserva() === $this) {
                $pagosMercadoPago->setSolicitudReserva(null);
            }
        }

        return $this;
    }


}
