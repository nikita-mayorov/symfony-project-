<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Enum\TaskPriority;
use App\Domain\Enum\TaskStatus;
use App\Domain\Exception\TaskAccessDeniedException;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Entity\Task;
use App\Entity\User;

final class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {
    }

    /**
     * @return list<Task>
     */
    public function list(User $user): array
    {
        return $this->taskRepository->findByUser($user);
    }

    /**
     * @return array{items: list<Task>, total: int, page: int, pages: int}
     */
    public function listPaginated(User $user, int $page, int $limit, ?TaskStatus $status = null, ?TaskPriority $priority = null): array
    {
        return $this->taskRepository->findByUserPaginated($user, $page, $limit, $status, $priority);
    }

    public function create(Task $task, User $owner): Task
    {
        $task->setOwner($owner);
        $this->taskRepository->save($task);

        return $task;
    }

    public function update(Task $task, User $user): Task
    {
        $this->assertOwner($task, $user);
        $this->taskRepository->save($task);

        return $task;
    }

    public function delete(Task $task, User $user): void
    {
        $this->assertOwner($task, $user);
        $this->taskRepository->remove($task);
    }

    public function findOne(int $id, User $user): Task
    {
        $task = $this->taskRepository->findById($id);

        if (null === $task) {
            throw new TaskAccessDeniedException($id);
        }

        $this->assertOwner($task, $user);

        return $task;
    }

    /**
     * @return array{todo: int, in_progress: int, done: int, total: int}
     */
    public function getStats(User $user): array
    {
        $tasks = $this->taskRepository->findByUser($user);

        $todo = 0;
        $inProgress = 0;
        $done = 0;

        foreach ($tasks as $task) {
            match ($task->getStatus()) {
                TaskStatus::Todo => $todo++,
                TaskStatus::InProgress => $inProgress++,
                TaskStatus::Done => $done++,
            };
        }

        return [
            'todo' => $todo,
            'in_progress' => $inProgress,
            'done' => $done,
            'total' => count($tasks),
        ];
    }

    private function assertOwner(Task $task, User $user): void
    {
        if ($task->getOwner()->getId() !== $user->getId()) {
            throw new TaskAccessDeniedException((int) $task->getId());
        }
    }
}
