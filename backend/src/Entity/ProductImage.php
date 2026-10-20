<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity]
#[ORM\Table(name: 'product_images')]
#[ORM\UniqueConstraint(name: 'product_images_product_id_url_key', columns: ['product_id', 'url'])]
final class ProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(length: 2048)]
    private string $url;

    #[ORM\Column(length: 255)]
    private string $path;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $position;

    public function __construct(Product $product, string $url, string $path, int $position)
    {
        $this->product = $product;
        $this->url = $url;
        $this->path = $path;
        $this->position = $position;
    }

    public function getId(): int
    {
        return $this->id ?? throw new LogicException('Изображение ещё не сохранено');
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
