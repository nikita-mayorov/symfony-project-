<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Enum\TaskPriority;
use App\Domain\Enum\TaskStatus;
use App\Entity\Task;
use App\Entity\User;

interface TaskRepositoryInterface
{
    /**
     * @return list<Task>
     */
    public function findByUser(User $user): array;

    /**
     * @return array{items: list<Task>, total: int, page: int, pages: int}
     */
    public function findByUserPaginated(User $user, int $page, int $limit, ?TaskStatus $status = null, ?TaskPriority $priority = null): array;

    public function findById(int $id): ?Task;

    public function save(Task $task): void;

    public function remove(Task $task): void;
}
