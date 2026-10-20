<?php

declare(strict_types=1);

namespace App\Tests\Integration\Doctrine;

use App\Doctrine\ResettableEntityManager;
use App\Repository\ProductRepository;
use App\Tests\Support\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class ResettableEntityManagerTest extends DatabaseTestCase
{
    public function testReopensAfterFailedFlush(): void
    {
        $em = $this->container->get(ResettableEntityManager::class);
        $products = $this->container->get(ProductRepository::class);

        $products->save($this->product('omJZSvxeiZU3YJ0-SMQbd2'));

        $failed = false;

        try {
            $products->save($this->product('omJZSvxeiZU3YJ0-SMQbd2'));
        } catch (UniqueConstraintViolationException) {
            $failed = true;
        }

        self::assertTrue($failed);
        self::assertFalse($em->isOpen());

        $em->reset();
        $products->save($this->product('RGwQX2jpj10nbyIXqB52r2'));

        self::assertTrue($em->isOpen());
        self::assertNotNull($products->findByExternalCode('RGwQX2jpj10nbyIXqB52r2'));
    }

    public function testResetDetachesEntitiesOfOpenManager(): void
    {
        $em = $this->container->get(ResettableEntityManager::class);
        $product = $this->product('kxNTbNeJiHRZH3gE47ntk2');
        $this->container->get(ProductRepository::class)->save($product);

        $em->reset();

        self::assertTrue($em->isOpen());
        self::assertFalse($em->contains($product));
    }
}
