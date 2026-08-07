<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateUserCommandTest extends KernelTestCase
{
    public function testCreateUser(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => 'cmduser@test.com',
            'password' => 'password123',
        ]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('User cmduser@test.com created.', $commandTester->getDisplay());

        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $user = $userRepository->findByEmail('cmduser@test.com');
        $this->assertNotNull($user);
        $this->assertTrue($user->isVerified());
    }

    public function testShortPasswordRejected(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => 'short@test.com',
            'password' => '12',
        ]);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('Password must be at least 8 characters', $commandTester->getDisplay());
    }
}
