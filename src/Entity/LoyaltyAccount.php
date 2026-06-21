<?php

namespace App\Entity;

use App\Repository\LoyaltyAccountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoyaltyAccountRepository::class)]
class LoyaltyAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'loyaltyAccount')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\Column]
    private ?int $pointsBalance = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tier = null;

    #[ORM\Column]
    private ?int $lifetimePoints = null;

    public function __construct()
    {
        $this->pointsBalance = 0;
        $this->lifetimePoints = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPointsBalance(): ?int
    {
        return $this->pointsBalance;
    }

    public function setPointsBalance(int $pointsBalance): static
    {
        $this->pointsBalance = $pointsBalance;

        return $this;
    }

    public function getTier(): ?string
    {
        return $this->tier;
    }

    public function setTier(?string $tier): static
    {
        $this->tier = $tier;

        return $this;
    }

    public function getLifetimePoints(): ?int
    {
        return $this->lifetimePoints;
    }

    public function setLifetimePoints(int $lifetimePoints): static
    {
        $this->lifetimePoints = $lifetimePoints;

        return $this;
    }
}
