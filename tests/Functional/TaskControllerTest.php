<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    private function createVerifiedUser(string $email, string $password): User
    {
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setIsVerified(true);
        $userRepository->save($user);

        return $user;
    }

    private function createTask(User $owner, string $title, ?\DateTimeImmutable $dueDate = null): Task
    {
        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);

        $task = new Task();
        $task->setTitle($title);
        $task->setOwner($owner);
        if (null !== $dueDate) {
            $task->setDueDate($dueDate);
        }
        $taskRepository->save($task);

        return $task;
    }

    private function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }

    public function testIndexShowsOnlyOwnTasks(): void
    {
        $user1 = $this->createVerifiedUser('user1@test.com', 'password');
        $user2 = $this->createVerifiedUser('user2@test.com', 'password');

        $this->createTask($user1, 'User 1 Task');
        $this->createTask($user2, 'User 2 Task');

        $this->loginUser($user1);
        $crawler = $this->client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table', 'User 1 Task');
        $this->assertSelectorTextNotContains('table', 'User 2 Task');
    }

    public function testCreateTask(): void
    {
        $user = $this->createVerifiedUser('create@test.com', 'password');
        $this->loginUser($user);

        $crawler = $this->client->request('GET', '/tasks/new');
        $form = $crawler->selectButton('Create Task')->form();

        $this->client->submit($form, [
            'task[title]' => 'New Test Task',
            'task[description]' => 'A task description',
            'task[status]' => 'todo',
            'task[priority]' => 'high',
        ]);

        $this->assertResponseRedirects('/tasks');

        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);
        $tasks = $taskRepository->findByUser($user);
        $this->assertCount(1, $tasks);
        $this->assertSame('New Test Task', $tasks[0]->getTitle());
    }

    public function testCreateTaskWithPastDueDate(): void
    {
        $user = $this->createVerifiedUser('pastdue@test.com', 'password');
        $this->loginUser($user);

        $crawler = $this->client->request('GET', '/tasks/new');
        $form = $crawler->selectButton('Create Task')->form();

        $this->client->submit($form, [
            'task[title]' => 'Past Due Task',
            'task[dueDate]' => '1111-11-18',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'Due date cannot be in the past');
    }

    public function testCreateTaskWithFutureDueDate(): void
    {
        $user = $this->createVerifiedUser('futuredue@test.com', 'password');
        $this->loginUser($user);

        $crawler = $this->client->request('GET', '/tasks/new');
        $form = $crawler->selectButton('Create Task')->form();

        $tomorrow = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');

        $this->client->submit($form, [
            'task[title]' => 'Future Task',
            'task[dueDate]' => $tomorrow,
        ]);

        $this->assertResponseRedirects('/tasks');
    }

    public function testShowOwnTask(): void
    {
        $user = $this->createVerifiedUser('show@test.com', 'password');
        $task = $this->createTask($user, 'My Task Title');

        $this->loginUser($user);
        $this->client->request('GET', '/tasks/'.$task->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My Task Title');
    }

    public function testShowOthersTaskReturns404(): void
    {
        $user1 = $this->createVerifiedUser('show1@test.com', 'password');
        $user2 = $this->createVerifiedUser('show2@test.com', 'password');
        $task = $this->createTask($user1, 'Secret Task');

        $this->loginUser($user2);
        $this->client->request('GET', '/tasks/'.$task->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditOwnTask(): void
    {
        $user = $this->createVerifiedUser('edit@test.com', 'password');
        $task = $this->createTask($user, 'Original Title');

        $this->loginUser($user);

        $crawler = $this->client->request('GET', '/tasks/'.$task->getId().'/edit');
        $form = $crawler->selectButton('Save Changes')->form();

        $this->client->submit($form, [
            'task[title]' => 'Updated Title',
            'task[status]' => 'in_progress',
            'task[priority]' => 'low',
        ]);

        $this->assertResponseRedirects('/tasks');

        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);
        $updated = $taskRepository->findById($task->getId());
        $this->assertNotNull($updated);
        $this->assertSame('Updated Title', $updated->getTitle());
    }

    public function testEditOthersTaskReturns404(): void
    {
        $user1 = $this->createVerifiedUser('edit1@test.com', 'password');
        $user2 = $this->createVerifiedUser('edit2@test.com', 'password');
        $task = $this->createTask($user1, 'Not Your Task');

        $this->loginUser($user2);
        $this->client->request('GET', '/tasks/'.$task->getId().'/edit');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteOwnTask(): void
    {
        $user = $this->createVerifiedUser('delete@test.com', 'password');
        $task = $this->createTask($user, 'Delete Me');

        $this->loginUser($user);

        $crawler = $this->client->request('GET', '/tasks/'.$task->getId());
        $this->assertSelectorExists('form[action="/tasks/'.$task->getId().'/delete"]');

        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/tasks/'.$task->getId().'/delete', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/tasks');

        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);
        $this->assertNull($taskRepository->findById($task->getId()));
    }

    public function testDeleteOthersTaskReturns404(): void
    {
        $user1 = $this->createVerifiedUser('del1@test.com', 'password');
        $user2 = $this->createVerifiedUser('del2@test.com', 'password');
        $task = $this->createTask($user1, 'Not Yours');

        $this->loginUser($user2);
        $this->client->request('POST', '/tasks/'.$task->getId().'/delete', [
            '_token' => 'fake-token',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnauthenticatedRedirectsToLogin(): void
    {
        $this->client->request('GET', '/tasks');

        $this->assertResponseRedirects('/login');
    }

    public function testFilterByStatus(): void
    {
        $user = $this->createVerifiedUser('filter@test.com', 'password');
        $this->createTask($user, 'Todo Task');
        $task = new Task();
        $task->setTitle('Done Task');
        $task->setOwner($user);
        $task->setStatus(\App\Domain\Enum\TaskStatus::Done);
        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);
        $taskRepository->save($task);

        $this->loginUser($user);
        $crawler = $this->client->request('GET', '/tasks?status=done');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table', 'Done Task');
        $this->assertSelectorTextNotContains('table', 'Todo Task');
    }

    public function testFilterByPriority(): void
    {
        $user = $this->createVerifiedUser('filterprio@test.com', 'password');
        $this->createTask($user, 'Low Task');
        $task = new Task();
        $task->setTitle('High Task');
        $task->setOwner($user);
        $task->setPriority(\App\Domain\Enum\TaskPriority::High);
        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);
        $taskRepository->save($task);

        $this->loginUser($user);
        $crawler = $this->client->request('GET', '/tasks?priority=high');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table', 'High Task');
        $this->assertSelectorTextNotContains('table', 'Low Task');
    }

    public function testPagination(): void
    {
        $user = $this->createVerifiedUser('pagination@test.com', 'password');

        for ($i = 1; $i <= 12; ++$i) {
            $this->createTask($user, "Task {$i}");
        }

        $this->loginUser($user);
        $crawler = $this->client->request('GET', '/tasks?page=1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.pagination', '1');
        $this->assertSelectorTextContains('.pagination', 'Next');
    }
}
