<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crée un utilisateur administrateur.')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepository $repo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email')
            ->addArgument('pseudonym', InputArgument::REQUIRED, 'Pseudonyme (3-30, alphanumérique/_/-)')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Mot de passe (sinon demandé interactivement)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $email */
        $email = $input->getArgument('email');
        /** @var string $pseudo */
        $pseudo = $input->getArgument('pseudonym');

        if (null !== $this->repo->findOneBy(['email' => $email])) {
            $io->error(sprintf('Email "%s" déjà utilisé.', $email));

            return Command::FAILURE;
        }

        /** @var string|null $password */
        $password = $input->getOption('password');
        if (null === $password || '' === $password) {
            $password = $io->askHidden('Mot de passe');
            if (null === $password || \strlen($password) < 8) {
                $io->error('Mot de passe vide ou trop court (8 caractères min).');

                return Command::FAILURE;
            }
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPseudonym($pseudo);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Admin créé : %s (uid=%s)', $email, $user->getUid()));

        return Command::SUCCESS;
    }
}
