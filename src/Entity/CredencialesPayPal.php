<?php

namespace App\Entity;

use App\Repository\CredencialesPayPalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CredencialesPayPalRepository::class)]
class CredencialesPayPal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $client_id = null;

    #[ORM\Column(length: 255)]
    private ?string $client_secret = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $scope = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $access_token = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token_type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $app_id = null;

    #[ORM\Column(nullable: true)]
    private ?int $expires_in = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nonce = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $refresh_token = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, PayPalPago>
     */
    #[ORM\OneToMany(targetEntity: PayPalPago::class, mappedBy: 'credencialesPayPal')]
    private Collection $pagos;

    /**
     * @param int|null $id
     */
    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->pagos = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function setClientId(string $client_id): static
    {
        $this->client_id = $client_id;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->client_secret;
    }

    public function setClientSecret(string $client_secret): static
    {
        $this->client_secret = $client_secret;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    public function setAccessToken(?string $access_token): static
    {
        $this->access_token = $access_token;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getTokenType(): ?string
    {
        return $this->token_type;
    }

    public function setTokenType(?string $token_type): static
    {
        $this->token_type = $token_type;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getAppId(): ?string
    {
        return $this->app_id;
    }

    public function setAppId(string $app_id): static
    {
        $this->app_id = $app_id;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expires_in;
    }

    public function setExpiresIn(?int $expires_in): static
    {
        $this->expires_in = $expires_in;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setNonce(?string $nonce): static
    {
        $this->nonce = $nonce;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refresh_token;
    }

    public function setRefreshToken(?string $refresh_token): static
    {
        $this->refresh_token = $refresh_token;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        $this->updated_at = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    /**
     * @return Collection<int, PayPalPago>
     */
    public function getPagos(): Collection
    {
        return $this->pagos;
    }

    public function addPago(PayPalPago $pago): static
    {
        if (!$this->pagos->contains($pago)) {
            $this->pagos->add($pago);
            $pago->setCredencialesPayPal($this);
        }

        return $this;
    }

    public function removePago(PayPalPago $pago): static
    {
        if ($this->pagos->removeElement($pago)) {
            // set the owning side to null (unless already changed)
            if ($pago->getCredencialesPayPal() === $this) {
                $pago->setCredencialesPayPal(null);
            }
        }

        return $this;
    }
}
