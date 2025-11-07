<?php

namespace App\Entity;

use App\Repository\TransferShowcaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferShowcaseRepository::class)]
#[ORM\Table(name: 'transfer_showcase')]
class TransferShowcase
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $titulo = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(length: 10)]
    private string $tipo = self::TYPE_IMAGE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagen = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $videoEmbed = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $destacado = false;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $posicion = 0;

    #[ORM\Column]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column]
    private \DateTimeImmutable $actualizadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
        $this->actualizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): self
    {
        $this->titulo = $titulo;
        $this->touch();

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
        $this->touch();

        return $this;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        if (!in_array($tipo, [self::TYPE_IMAGE, self::TYPE_VIDEO], true)) {
            $tipo = self::TYPE_IMAGE;
        }

        $this->tipo = $tipo;
        $this->touch();

        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(?string $imagen): self
    {
        $this->imagen = $imagen;
        $this->touch();

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;
        $this->touch();

        return $this;
    }

    public function getVideoEmbed(): ?string
    {
        return $this->videoEmbed;
    }

    public function setVideoEmbed(?string $videoEmbed): self
    {
        $this->videoEmbed = $videoEmbed;
        $this->touch();

        return $this;
    }

    public function getVideoId(): ?string
    {
        if ($this->videoEmbed && preg_match('/embed\/([\w\-]{11})/i', $this->videoEmbed, $matches)) {
            return $matches[1];
        }

        if ($this->videoUrl && preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w\-]{11})/i', $this->videoUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getVideoBackgroundUrl(): ?string
    {
        $videoId = $this->getVideoId();

        if ($videoId === null) {
            return null;
        }

        return sprintf(
            'https://www.youtube.com/embed/%1$s?autoplay=1&mute=1&controls=0&loop=1&playlist=%1$s&modestbranding=1&playsinline=1&rel=0&showinfo=0',
            $videoId
        );
    }

    public function isDestacado(): bool
    {
        return $this->destacado;
    }

    public function setDestacado(bool $destacado): self
    {
        $this->destacado = $destacado;
        $this->touch();

        return $this;
    }

    public function getPosicion(): int
    {
        return $this->posicion;
    }

    public function setPosicion(int $posicion): self
    {
        $this->posicion = $posicion;
        $this->touch();

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getActualizadoEn(): \DateTimeImmutable
    {
        return $this->actualizadoEn;
    }

    private function touch(): void
    {
        $this->actualizadoEn = new \DateTimeImmutable();
    }
}
