<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class DatabaseTestCase extends TestCase
{
    protected ContainerInterface $container;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->container = (require dirname(__DIR__, 2) . '/config/bootstrap.php')(
            $this->definitions() + [LoggerInterface::class => new NullLogger()],
        );
        $this->em = $this->container->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->em->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $connection->close();
    }

    /**
     * @return array<string, mixed>
     */
    protected function definitions(): array
    {
        return [];
    }

    protected function countRows(string $table): int
    {
        return (int) $this->em->getConnection()->fetchOne(sprintf('SELECT count(*) FROM %s', $table));
    }

    protected function createUser(string $email = 'manager@example.com', string $password = 'secret-password'): User
    {
        $user = new User($email, password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]));
        $this->container->get(UserRepository::class)->save($user);

        return $user;
    }

    protected function product(string $externalCode): Product
    {
        return new Product($externalCode, 'Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ', 'Домашние бермуды', '1320.00', '33.33');
    }
}
