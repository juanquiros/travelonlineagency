<?php

namespace App\Entity;

use App\Repository\TransferFormFieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferFormFieldRepository::class)]
#[ORM\Table(name: 'transfer_form_field')]
class TransferFormField
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_NUMBER = 'number';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $clave = '';

    #[ORM\Column(length: 255)]
    private string $etiqueta = '';

    #[ORM\Column(length: 30)]
    private string $tipo = self::TYPE_TEXT;

    #[ORM\Column]
    private bool $requerido = true;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $opciones = null;

    #[ORM\Column]
    private int $orden = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClave(): string
    {
        return $this->clave;
    }

    public function setClave(string $clave): self
    {
        $this->clave = $clave;

        return $this;
    }

    public function getEtiqueta(): string
    {
        return $this->etiqueta;
    }

    public function setEtiqueta(string $etiqueta): self
    {
        $this->etiqueta = $etiqueta;

        return $this;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function isRequerido(): bool
    {
        return $this->requerido;
    }

    public function setRequerido(bool $requerido): self
    {
        $this->requerido = $requerido;

        return $this;
    }

    public function getOpciones(): ?array
    {
        return $this->opciones;
    }

    public function setOpciones(?array $opciones): self
    {
        $this->opciones = $opciones;

        return $this;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): self
    {
        $this->orden = $orden;

        return $this;
    }
}
