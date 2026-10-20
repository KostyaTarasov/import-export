<?php

declare(strict_types=1);

namespace App\Doctrine;

use Closure;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;

final class ResettableEntityManager extends EntityManagerDecorator
{
    /**
     * @param Closure(): EntityManagerInterface $factory
     */
    public function __construct(
        private readonly Closure $factory,
    ) {
        parent::__construct(($this->factory)());
    }

    public function reset(): void
    {
        if ($this->isOpen()) {
            $this->clear();

            return;
        }

        $this->wrapped = ($this->factory)();
    }
}
