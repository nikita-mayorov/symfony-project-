<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TaskLifecycleSubscriberTest extends KernelTestCase
{
    private function createUser(): User
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail('subscriber@test.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setIsVerified(true);
        $userRepository->save($user);

        return $user;
    }

    public function testTaskCreationIsLogged(): void
    {
        self::bootKernel();

        $logFile = self::$kernel->getLogDir().'/task.log';

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $user = $this->createUser();
        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);

        $task = new Task();
        $task->setTitle('Subscriber Task');
        $task->setOwner($user);
        $taskRepository->save($task);

        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('Task created', $content);
        $this->assertStringContainsString('Subscriber Task', $content);
    }

    public function testTaskDeletionIsLogged(): void
    {
        self::bootKernel();

        $logFile = self::$kernel->getLogDir().'/task.log';

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $user = $this->createUser();
        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);

        $task = new Task();
        $task->setTitle('Delete Log Task');
        $task->setOwner($user);
        $taskRepository->save($task);

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $taskRepository->remove($task);

        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('Task deleted', $content);
        $this->assertStringContainsString('Delete Log Task', $content);
    }
}
