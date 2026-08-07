<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Repository\TaskRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TaskApiControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    private function createUser(string $email, string $password): User
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

    private function createTask(User $owner, string $title): Task
    {
        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);

        $task = new Task();
        $task->setTitle($title);
        $task->setOwner($owner);
        $taskRepository->save($task);

        return $task;
    }

    private function requestWithToken(string $method, string $uri, ?array $data = null, ?User $user = null): void
    {
        $server = [];
        if (null !== $user) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$user->getApiToken();
        }

        if (null !== $data) {
            $this->client->jsonRequest($method, $uri, $data, $server);
        } else {
            $this->client->request($method, $uri, [], [], $server);
        }
    }

    public function testIndexReturnsTasks(): void
    {
        $user = $this->createUser('apiuser@test.com', 'password');
        $this->createTask($user, 'API Task 1');
        $this->createTask($user, 'API Task 2');

        $this->requestWithToken('GET', '/api/tasks', null, $user);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $tasks = json_decode($content, true);

        $this->assertCount(2, $tasks);
        $this->assertSame('API Task 1', $tasks[0]['title']);
    }

    public function testIndexShowsOnlyOwnTasks(): void
    {
        $user1 = $this->createUser('api1@test.com', 'password');
        $user2 = $this->createUser('api2@test.com', 'password');

        $this->createTask($user1, 'User 1 Task');
        $this->createTask($user2, 'User 2 Task');

        $this->requestWithToken('GET', '/api/tasks', null, $user1);

        $content = $this->client->getResponse()->getContent();
        $tasks = json_decode($content, true);

        $this->assertCount(1, $tasks);
        $this->assertSame('User 1 Task', $tasks[0]['title']);
    }

    public function testShowReturnsTask(): void
    {
        $user = $this->createUser('apishow@test.com', 'password');
        $task = $this->createTask($user, 'Show Me');

        $this->requestWithToken('GET', '/api/tasks/'.$task->getId(), null, $user);

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame('Show Me', $data['title']);
        $this->assertArrayHasKey('owner', $data);
        $this->assertSame($user->getId(), $data['owner']['id']);
        $this->assertSame('apishow@test.com', $data['owner']['email']);
    }

    public function testShowOthersTaskReturns404(): void
    {
        $user1 = $this->createUser('apishow1@test.com', 'password');
        $user2 = $this->createUser('apishow2@test.com', 'password');
        $task = $this->createTask($user1, 'Secret');

        $this->requestWithToken('GET', '/api/tasks/'.$task->getId(), null, $user2);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateTask(): void
    {
        $user = $this->createUser('apicreate@test.com', 'password');

        $this->requestWithToken('POST', '/api/tasks', [
            'title' => 'API Created Task',
            'status' => 'todo',
            'priority' => 'high',
        ], $user);

        $this->assertResponseStatusCodeSame(201);

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame('API Created Task', $data['title']);
        $this->assertSame('high', $data['priority']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testCreateTaskWithEmptyTitle(): void
    {
        $user = $this->createUser('apiempty@test.com', 'password');

        $this->requestWithToken('POST', '/api/tasks', [
            'title' => '',
        ], $user);

        $this->assertResponseStatusCodeSame(422);

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame(422, $data['status']);
        $this->assertArrayHasKey('violations', $data);
    }

    public function testCreateTaskWithPastDueDate(): void
    {
        $user = $this->createUser('apipast@test.com', 'password');

        $this->requestWithToken('POST', '/api/tasks', [
            'title' => 'Past Due',
            'dueDate' => '1111-11-18',
        ], $user);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateTask(): void
    {
        $user = $this->createUser('apiupdate@test.com', 'password');
        $task = $this->createTask($user, 'Old Title');

        $this->requestWithToken('PUT', '/api/tasks/'.$task->getId(), [
            'title' => 'New Title',
            'status' => 'done',
            'priority' => 'low',
        ], $user);

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame('New Title', $data['title']);
        $this->assertSame('done', $data['status']);
    }

    public function testUpdateOthersTaskReturns404(): void
    {
        $user1 = $this->createUser('apiupdate1@test.com', 'password');
        $user2 = $this->createUser('apiupdate2@test.com', 'password');
        $task = $this->createTask($user1, 'Not Yours');

        $this->requestWithToken('PUT', '/api/tasks/'.$task->getId(), [
            'title' => 'Hacked',
        ], $user2);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteTask(): void
    {
        $user = $this->createUser('apidelete@test.com', 'password');
        $task = $this->createTask($user, 'Delete Me');
        $taskId = (int) $task->getId();

        $this->requestWithToken('DELETE', '/api/tasks/'.$taskId, null, $user);

        $this->assertResponseStatusCodeSame(204);

        $taskRepository = static::getContainer()->get(TaskRepositoryInterface::class);
        $this->assertNull($taskRepository->findById($taskId));
    }

    public function testDeleteOthersTaskReturns404(): void
    {
        $user1 = $this->createUser('apidel1@test.com', 'password');
        $user2 = $this->createUser('apidel2@test.com', 'password');
        $task = $this->createTask($user1, 'Not Yours');

        $this->requestWithToken('DELETE', '/api/tasks/'.$task->getId(), null, $user2);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnauthenticatedReturns401(): void
    {
        $this->client->request('GET', '/api/tasks');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testInvalidTokenReturns401(): void
    {
        $this->client->request('GET', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
