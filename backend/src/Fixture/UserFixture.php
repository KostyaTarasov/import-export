<?php

declare(strict_types=1);

namespace App\Fixture;

use App\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

final readonly class UserFixture implements FixtureInterface
{
    public function __construct(
        private string $email,
        private string $password,
    ) {}

    public function load(ObjectManager $manager): void
    {
        if ($this->email === '' || $this->password === '') {
            throw new RuntimeException('Задайте ADMIN_EMAIL и ADMIN_PASSWORD в .env');
        }

        $manager->persist(new User($this->email, password_hash($this->password, PASSWORD_DEFAULT)));
        $manager->flush();
    }
}
