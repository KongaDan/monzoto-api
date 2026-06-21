<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    const PAYMENT_STATUS_PENDING = 1;
    const PAYMENT_STATUS_PAID = 2;
    const PAYMENT_STATUS_FAILED = 3;
    const PAYMENT_STATUS_REFUNDED = 4;
    const PAYMENT_STATUS_CANCEL = 5;

    const FULFILLMENT_STATUS_PENDING = 1;
    const FULFILLMENT_STATUS_CONFIRMED = 2;
    const FULFILLMENT_STATUS_IN_TRANSIT = 3;
    const FULFILLMENT_STATUS_DELIVERED = 4;
    const FULFILLMENT_STATUS_CANCELLED = 5;

    const PAYMENT_METHOD_CREDIT_CARD = 1;
    const PAYMENT_METHOD_MOBILE_MONEY = 2;
    const PAYEMENT_METHOD_AT_DELIVERY = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;
    
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\Column]
    private ?bool $isPaid = null;

    #[ORM\Column]
    private ?int $paymentStatus = null;

    #[ORM\Column]
    private ?int $FulfillmentStatus = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    // Total de la commande avant application des remises et ajout des frais de livraison
    #[ORM\Column]
    private ?float $subTotal = null;

    // Total des remises appliquées à la commande
    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?float $discountTotal = null;

    // Coût de livraison pour la commande
    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?float $shippingCost = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $currencyShipping = null;

    // Total final de la commande après application des remises et ajout des frais de livraison
    #[ORM\Column]
    #[Groups(['order:read', 'order:list'])]
    private ?float $total = null;

    #[ORM\Column(length: 10)]
    #[Groups(['order:read', 'order:list'])]
    private ?string $currency = null;

    // Méthode de paiement utilisée pour la commande
    #[ORM\Column(nullable: true)]
    #[Groups(['order:read'])]
    private ?int $paymentMethod = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $customerFirstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $customerLastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $customerAddress = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read', 'order:list'])]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['order:read'])]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['order:read', 'order:list'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDeleted = null;

    #[ORM\Column(nullable: true)]
    private ?float $taxTotal = null;

    #[ORM\ManyToOne]
    private ?ShippingMethod $shippingMethod = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $carrier = null;

    #[ORM\Column]
    private ?bool $giftWrap = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $giftMessage = null;

    #[ORM\ManyToOne]
    private ?Address $shippingAddress = null;

    #[ORM\ManyToOne]
    private ?Address $billingAddress = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isPaid = false;
        $this->paymentStatus = self::PAYMENT_STATUS_PENDING;
        $this->FulfillmentStatus = self::FULFILLMENT_STATUS_PENDING;
        $this->code = bin2hex(random_bytes(10));
        $this->giftWrap = false;
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

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
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

    public function getPaymentStatus(): ?int
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(int $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getFulfillmentStatus(): ?int
    {
        return $this->FulfillmentStatus;
    }

    public function setFulfillmentStatus(int $FulfillmentStatus): static
    {
        $this->FulfillmentStatus = $FulfillmentStatus;

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getSubTotal(): ?float
    {
        return $this->subTotal;
    }

    public function setSubTotal(float $subTotal): static
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    public function getDiscountTotal(): ?float
    {
        return $this->discountTotal;
    }

    public function setDiscountTotal(?float $discountTotal): static
    {
        $this->discountTotal = $discountTotal;

        return $this;
    }

    public function getShippingCost(): ?float
    {
        return $this->shippingCost;
    }

    public function setShippingCost(?float $shippingCost): static
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

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

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getCustomerFirstName(): ?string
    {
        return $this->customerFirstName;
    }

    public function setCustomerFirstName(?string $customerFirstName): static
    {
        $this->customerFirstName = $customerFirstName;

        return $this;
    }

    public function getCustomerLastName(): ?string
    {
        return $this->customerLastName;
    }

    public function setCustomerLastName(?string $customerLastName): static
    {
        $this->customerLastName = $customerLastName;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    public function getCustomerAddress(): ?string
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(?string $customerAddress): static
    {
        $this->customerAddress = $customerAddress;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPaymentMethod(): ?int
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?int $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getCurrencyShipping(): ?string
    {
        return $this->currencyShipping;
    }

    public function setCurrencyShipping(?string $currencyShipping): static
    {
        $this->currencyShipping = $currencyShipping;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getTaxTotal(): ?float
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(?float $taxTotal): static
    {
        $this->taxTotal = $taxTotal;

        return $this;
    }

    public function getShippingMethod(): ?ShippingMethod
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(?ShippingMethod $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function isGiftWrap(): ?bool
    {
        return $this->giftWrap;
    }

    public function setGiftWrap(bool $giftWrap): static
    {
        $this->giftWrap = $giftWrap;

        return $this;
    }

    public function getGiftMessage(): ?string
    {
        return $this->giftMessage;
    }

    public function setGiftMessage(?string $giftMessage): static
    {
        $this->giftMessage = $giftMessage;

        return $this;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getRefundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(?\DateTimeImmutable $refundedAt): static
    {
        $this->refundedAt = $refundedAt;

        return $this;
    }
}
