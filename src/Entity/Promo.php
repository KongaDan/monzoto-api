<?php

namespace App\Entity;

use App\Repository\PromoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromoRepository::class)]
class Promo
{
    const TYPE_PERCENTAGE = 1;
    const TYPE_AMOUNT = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column]
    private ?int $type = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;
    
    #[ORM\Column]
    private ?int $usageLimit = null;

    #[ORM\Column]
    private ?int $usageCount = null;

    #[ORM\Column]
    private ?float $minOrderAmount = null;

    #[ORM\Column]
    private ?float $maxOrderAmount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    public function __construct()
    {
        $this->isActive = false;
        $this->usageCount = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(int $usageLimit): static
    {
        $this->usageLimit = $usageLimit;

        return $this;
    }

    public function getUsageCount(): ?int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;

        return $this;
    }

    public function getMinOrderAmount(): ?float
    {
        return $this->minOrderAmount;
    }

    public function setMinOrderAmount(float $minOrderAmount): static
    {
        $this->minOrderAmount = $minOrderAmount;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(\DateTimeImmutable $validTo): static
    {
        $this->validTo = $validTo;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getMaxOrderAmount(): ?float
    {
        return $this->maxOrderAmount;
    }

    public function setMaxOrderAmount(float $maxOrderAmount): static
    {
        $this->maxOrderAmount = $maxOrderAmount;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}
