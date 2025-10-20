<?php

namespace App\Entity;

use App\Repository\CredencialesMercadoPagoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CredencialesMercadoPagoRepository::class)]
class CredencialesMercadoPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $publicKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $clientSecret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tokenType = null;

    #[ORM\Column(nullable: true)]
    private ?int $expiresIn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fechavence = null;

    /**
     * @var Collection<int, MercadoPagoPago>
     */
    #[ORM\OneToMany(targetEntity: MercadoPagoPago::class, mappedBy: 'credencialesMercadoPago')]
    private Collection $mercadoPagoPagos;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->mercadoPagoPagos = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): static
    {
        $this->clientId = $clientId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): static
    {
        $this->accessToken = $accessToken;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(?string $publicKey): static
    {
        $this->publicKey = $publicKey;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): static
    {
        $this->clientSecret = $clientSecret;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    public function setTokenType(?string $tokenType): static
    {
        $this->tokenType = $tokenType;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(?int $expiresIn): static
    {
        if ($expiresIn === null) {
            $this->expiresIn = null;
            $this->updatedAt = new \DateTimeImmutable();
            $this->fechavence = null;

            return $this;
        }

        $this->expiresIn = max($expiresIn - 60, 0);
        $this->updatedAt = new \DateTimeImmutable();
        $interval = new \DateInterval('PT' . max($expiresIn, 0) . 'S');
        $this->fechavence = $this->updatedAt->add($interval);
        return $this;
    }

    public function tokenisvalid(): bool
    {
        if (!$this->fechavence instanceof \DateTimeInterface) {
            return false;
        }

        return $this->fechavence > new \DateTimeImmutable();
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): static
    {
        $this->userId = $userId;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getFechavence(): ?\DateTimeImmutable
    {
        return $this->fechavence;
    }

    public function setFechavence(?\DateTimeImmutable $fechavence): static
    {
        $this->fechavence = $fechavence;

        return $this;
    }

    public function clearTokens(): static
    {
        $this->accessToken = null;
        $this->refreshToken = null;
        $this->tokenType = null;
        $this->scope = null;
        $this->userId = null;
        $this->fechavence = null;
        $this->nickname = null;
        $this->email = null;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, MercadoPagoPago>
     */
    public function getMercadoPagoPagos(): Collection
    {
        return $this->mercadoPagoPagos;
    }

    public function addMercadoPagoPago(MercadoPagoPago $mercadoPagoPago): static
    {
        if (!$this->mercadoPagoPagos->contains($mercadoPagoPago)) {
            $this->mercadoPagoPagos->add($mercadoPagoPago);
            $mercadoPagoPago->setCredencialesMercadoPago($this);
        }

        return $this;
    }

    public function removeMercadoPagoPago(MercadoPagoPago $mercadoPagoPago): static
    {
        if ($this->mercadoPagoPagos->removeElement($mercadoPagoPago)) {
            // set the owning side to null (unless already changed)
            if ($mercadoPagoPago->getCredencialesMercadoPago() === $this) {
                $mercadoPagoPago->setCredencialesMercadoPago(null);
            }
        }

        return $this;
    }


}
