<?php

namespace App\Tests\Command;

use App\Command\PromoteUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PromoteUserCommandTest extends TestCase
{
    public function testPromotesUserWithDefaultRole(): void
    {
        $user = new User('admin@example.com', 'Admin', 'User');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('admin@example.com')
            ->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $command = new PromoteUserCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);

        $statusCode = $tester->execute([
            'email' => 'admin@example.com',
        ]);

        self::assertSame(0, $statusCode);
        self::assertContains('ROLE_ADMIN', $user->getRoles());
        self::assertStringContainsString('Granted "ROLE_ADMIN"', $tester->getDisplay());
    }

    public function testFailsWhenUserNotFound(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneByEmail')
            ->with('missing@example.com')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $command = new PromoteUserCommand($userRepository, $entityManager);
        $tester = new CommandTester($command);

        $statusCode = $tester->execute([
            'email' => 'missing@example.com',
        ]);

        self::assertSame(1, $statusCode);
        self::assertStringContainsString('No user found', $tester->getDisplay());
    }
}

