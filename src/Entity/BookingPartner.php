<?php

namespace App\Entity;

use App\Repository\BookingPartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingPartnerRepository::class)]
class BookingPartner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;



    #[ORM\Column]
    private ?bool $habilitado = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'bookingPartner')]
    private Collection $bookings;

    #[ORM\OneToOne(inversedBy: 'bPartner', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuario $Usuario = null;

    #[ORM\Column(nullable: true)]
    private ?float $comisionPlataforma = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->habilitado = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }







    public function isHabilitado(): ?bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): static
    {
        $this->habilitado = $habilitado;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setBookingPartner($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getBookingPartner() === $this) {
                $booking->setBookingPartner(null);
            }
        }

        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->Usuario;
    }

    public function setUsuario(Usuario $Usuario): static
    {
        $this->Usuario = $Usuario;

        return $this;
    }

    public function getComisionPlataforma(): ?float
    {
        return $this->comisionPlataforma;
    }

    public function setComisionPlataforma(?float $comisionPlataforma): static
    {
        $this->comisionPlataforma = $comisionPlataforma;

        return $this;
    }
}
