<?php

declare(strict_types=1);

namespace App\Command;

use App\Fixture\ProductFixture;
use App\Fixture\UserFixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:fixtures:load', description: 'Заполнить базу демонстрационными товарами и создать администратора')]
final class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserFixture $userFixture,
        private readonly ProductFixture $productFixture,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->isInteractive() && !$io->confirm('Все товары и пользователи в базе будут удалены. Продолжить?', false)) {
            return Command::SUCCESS;
        }

        $loader = new Loader();
        $loader->addFixture($this->userFixture);
        $loader->addFixture($this->productFixture);

        $executor = new ORMExecutor($this->em, new ORMPurger($this->em));
        $executor->execute($loader->getFixtures());

        $io->success('Демонстрационные данные загружены');

        return Command::SUCCESS;
    }
}
