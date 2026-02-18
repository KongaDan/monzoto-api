<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Cart
{
    const STATUS_ACTIVE = 1;
    const STATUS_CONVERTED = 2;
    const STATUS_ABANDONED = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?User $customer = null;

    #[ORM\Column(nullable: true)]
    private ?int $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $promoCode = null;

    #[ORM\Column(nullable: true)]
    private ?float $discountAmount = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;


    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $cartItems;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $sessionToken = null;

    #[ORM\Column(nullable: true)]
    private ?float $subPrice = null;

    public function __construct()
    {
        $this->status = self::STATUS_ACTIVE;
        $this->price = 0.0;
        $this->cartItems = new ArrayCollection();
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPromoCode(): ?string
    {
        return $this->promoCode;
    }

    public function setPromoCode(?string $promoCode): static
    {
        $this->promoCode = $promoCode;

        return $this;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?float $discountAmount): static
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

        public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(?string $sessionToken): static
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

    public function getSubPrice(): ?float
    {
        return $this->subPrice;
    }

    public function setSubPrice(float $subPrice): static
    {
        $this->subPrice = $subPrice;

        return $this;
    }

}
