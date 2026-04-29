<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\GameScore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:scores:purge', description: 'Supprime les scores plus vieux que N jours.')]
final class PurgeOldScoresCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Ancienneté en jours', '365')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compter sans supprimer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = max(1, (int) $input->getOption('days'));
        $threshold = (new \DateTimeImmutable())->modify(sprintf('-%d days', $days));

        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(s)')
            ->from(GameScore::class, 's')
            ->where('s.createdAt < :t')
            ->setParameter('t', $threshold)
            ->getQuery()
            ->getSingleScalarResult();

        if ($input->getOption('dry-run')) {
            $io->note(sprintf('Dry-run : %d scores seraient supprimés (avant %s).', $count, $threshold->format('Y-m-d')));

            return Command::SUCCESS;
        }

        $this->em->createQueryBuilder()
            ->delete(GameScore::class, 's')
            ->where('s.createdAt < :t')
            ->setParameter('t', $threshold)
            ->getQuery()
            ->execute();

        $io->success(sprintf('%d scores supprimés.', $count));

        return Command::SUCCESS;
    }
}
