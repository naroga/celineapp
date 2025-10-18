<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'user:promote',
    description: 'Grant an application role to an existing user.',
)]
final class PromoteUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address of the user to promote.')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Fully-qualified Symfony role (defaults to ROLE_ADMIN).', 'ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = trim((string) $input->getArgument('email'));
        $role = strtoupper(trim((string) $input->getOption('role')));

        if ($email === '') {
            $io->error('Email address is required.');

            return Command::INVALID;
        }

        if ($role === '' || !str_starts_with($role, 'ROLE_')) {
            $io->error('Roles must be provided in Symfony format (e.g. ROLE_ADMIN).');

            return Command::INVALID;
        }

        $user = $this->userRepository->findOneByEmail($email);

        if ($user === null) {
            $io->error(sprintf('No user found for email "%s".', $email));

            return Command::FAILURE;
        }

        $existingRoles = $user->getRoles();

        if (in_array($role, $existingRoles, true)) {
            $io->success(sprintf('User "%s" already has "%s".', $email, $role));

            return Command::SUCCESS;
        }

        $customRoles = array_values(array_filter(
            $existingRoles,
            static fn (string $value): bool => $value !== 'ROLE_USER',
        ));
        $customRoles[] = $role;

        $user->setRoles(array_values(array_unique($customRoles)));
        $this->entityManager->flush();

        $io->success(sprintf('Granted "%s" to "%s".', $role, $email));

        return Command::SUCCESS;
    }
}
