<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\LoadFixturesCommand;
use App\Tests\Support\DatabaseTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class LoadFixturesCommandTest extends DatabaseTestCase
{
    private string $storage;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/fixtures-' . bin2hex(random_bytes(4));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        new Filesystem()->remove($this->storage);
    }

    protected function definitions(): array
    {
        return [
            'path.storage' => $this->storage,
            'admin.email' => 'Admin@Example.com',
            'admin.password' => 'admin-password',
        ];
    }

    public function testFillsAllProductTables(): void
    {
        $this->em->getConnection()->executeStatement("INSERT INTO products (external_code, name, price, created_at, updated_at) VALUES ('old', 'Старый товар', 100, now(), now())");

        $tester = new CommandTester($this->container->get(LoadFixturesCommand::class));
        $tester->execute([], ['interactive' => false]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Демонстрационные данные загружены', $tester->getDisplay());
        self::assertSame(['admin@example.com'], $this->em->getConnection()->fetchFirstColumn('SELECT email FROM users'));
        self::assertSame(12, $this->countRows('products'));
        self::assertSame(60, $this->countRows('product_attributes'));
        self::assertSame(12, $this->countRows('product_images'));
        self::assertFalse($this->em->getConnection()->fetchOne("SELECT 1 FROM products WHERE external_code = 'old'"));

        $paths = $this->em->getConnection()->fetchFirstColumn('SELECT DISTINCT path FROM product_images');
        self::assertCount(4, $paths);

        foreach ($paths as $path) {
            self::assertFileExists($this->storage . '/' . $path);
        }
    }

    public function testAsksBeforePurging(): void
    {
        $tester = new CommandTester($this->container->get(LoadFixturesCommand::class));
        $tester->setInputs(['no']);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(0, $this->countRows('products'));
    }
}
