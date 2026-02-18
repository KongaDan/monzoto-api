<?php

namespace App\Entity;

use App\Repository\RateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RateRepository::class)]
class Rate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $currencyFrom = null;

    #[ORM\Column(length: 255)]
    private ?string $currencyTo = null;

    #[ORM\Column]
    private ?float $rate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiredAt = null;

    #[ORM\Column]
    private ?bool $isDeleted = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = false;
        $this->isDeleted = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrencyFrom(): ?string
    {
        return $this->currencyFrom;
    }

    public function setCurrencyFrom(string $currencyFrom): static
    {
        $this->currencyFrom = $currencyFrom;

        return $this;
    }

    public function getCurrencyTo(): ?string
    {
        return $this->currencyTo;
    }

    public function setCurrencyTo(string $currencyTo): static
    {
        $this->currencyTo = $currencyTo;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function getValidAt(): ?\DateTimeImmutable
    {
        return $this->validAt;
    }

    public function setValidAt(?\DateTimeImmutable $validAt): static
    {
        $this->validAt = $validAt;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeImmutable $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }
}
