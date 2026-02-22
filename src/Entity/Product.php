<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:list', 'product:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:detail'])]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:detail'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Type(type: 'numeric')]
    #[Assert\Positive]
    #[Assert\Regex(
    pattern: '/^\d+(\.\d{1,2})?$/',
    message: 'Le prix doit être un nombre décimal valide.'
)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $currency = null;

    #[ORM\Column]
    #[Groups(['product:detail'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:detail'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:list', 'product:detail'])]
    private ?string $picture = null;

    #[ORM\ManyToOne]
    #[Groups(['product:list', 'product:detail'])]
    private ?Category $category = null;

    #[ORM\Column]
    #[Groups(['product:list', 'product:detail'])]
    private ?bool $isActive = null;

    #[ORM\Column]
    #[Groups(['product:list', 'product:detail'])]
    private ?bool $isAvailable = null;

    #[ORM\Column]
    #[Groups(['product:list', 'product:detail'])]
    private ?bool $isBestSellerFlag = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?float $averageRating = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:list', 'product:detail'])]
    private ?int $reviewCount = null;

    #[ORM\OneToMany(targetEntity: ProductMedia::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    #[Groups(['product:detail'])]
    private Collection $productMedia;

    public function __construct()
    {
        $this->isActive = true;
        $this->isAvailable = true;
        $this->productMedia = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(string $picture): static
    {
        $this->picture = $picture;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

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

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    public function isBestSellerFlag(): ?bool
    {
        return $this->isBestSellerFlag;
    }

    public function setIsBestSellerFlag(bool $isBestSellerFlag): static
    {
        $this->isBestSellerFlag = $isBestSellerFlag;

        return $this;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): static
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getReviewCount(): ?int
    {
        return $this->reviewCount;
    }

    public function setReviewCount(?int $reviewCount): static
    {
        $this->reviewCount = $reviewCount;

        return $this;
    }

    /**
     * @return Collection<int, ProductMedia>
     */
    public function getProductMedia(): Collection
    {
        return $this->productMedia;
    }

    public function addProductMedia(ProductMedia $productMedia): static
    {
        if (!$this->productMedia->contains($productMedia)) {
            $this->productMedia->add($productMedia);
            $productMedia->setProduct($this);
        }

        return $this;
    }

    public function removeProductMedia(ProductMedia $productMedia): static
    {
        if ($this->productMedia->removeElement($productMedia)) {
            if ($productMedia->getProduct() === $this) {
                $productMedia->setProduct(null);
            }
        }

        return $this;
    }
}
