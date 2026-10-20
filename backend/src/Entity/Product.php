<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\UniqueConstraint(name: 'products_external_code_key', columns: ['external_code'])]
#[ORM\Index(name: 'products_price_idx', columns: ['price'])]
final class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $externalCode;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $price;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $discount;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, ProductAttribute>
     */
    #[ORM\OneToMany(targetEntity: ProductAttribute::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $attributes;

    /**
     * @var Collection<int, ProductImage>
     */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    public function __construct(string $externalCode, string $name, ?string $description, string $price, ?string $discount)
    {
        $this->externalCode = $externalCode;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->discount = $discount;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->attributes = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id ?? throw new LogicException('Товар ещё не сохранён');
    }

    public function getExternalCode(): string
    {
        return $this->externalCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(string $name, ?string $description, string $price, ?string $discount): void
    {
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->discount = $discount;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @return list<ProductAttribute>
     */
    public function getAttributes(): array
    {
        return $this->attributes->getValues();
    }

    public function setAttribute(string $key, string $value): void
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getKey() === $key) {
                $attribute->changeValue($value);

                return;
            }
        }

        $this->attributes->add(new ProductAttribute($this, $key, $value));
    }

    /**
     * @param array<string, string> $values
     */
    public function replaceAttributes(array $values): void
    {
        $stale = $this->attributes->filter(
            static fn(ProductAttribute $attribute): bool => !array_key_exists($attribute->getKey(), $values),
        );

        foreach ($stale as $attribute) {
            $this->attributes->removeElement($attribute);
        }

        foreach ($values as $key => $value) {
            $this->setAttribute((string) $key, $value);
        }
    }

    /**
     * @return list<ProductImage>
     */
    public function getImages(): array
    {
        return $this->images->getValues();
    }

    public function addImage(string $url, string $path): void
    {
        $position = 0;

        foreach ($this->images as $image) {
            $position = max($position, $image->getPosition() + 1);
        }

        $this->images->add(new ProductImage($this, $url, $path, $position));
    }

    /**
     * @param array<string, string> $paths
     */
    public function replaceImages(array $paths): void
    {
        $stale = $this->images->filter(
            static fn(ProductImage $image): bool => !array_key_exists($image->getUrl(), $paths),
        );

        foreach ($stale as $image) {
            $this->images->removeElement($image);
        }

        $position = 0;

        foreach ($paths as $url => $path) {
            $image = $this->findImage((string) $url);

            if ($image === null) {
                $this->images->add(new ProductImage($this, (string) $url, $path, $position));
            } else {
                $image->moveTo($position, $path);
            }

            ++$position;
        }
    }

    public function findImage(string $url): ?ProductImage
    {
        foreach ($this->images as $image) {
            if ($image->getUrl() === $url) {
                return $image;
            }
        }

        return null;
    }
}
