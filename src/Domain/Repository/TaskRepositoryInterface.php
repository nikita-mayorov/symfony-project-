<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Entity\Task;
use App\Entity\User;

interface TaskRepositoryInterface
{
    /**
     * @return list<Task>
     */
    public function findByUser(User $user): array;

    public function findById(int $id): ?Task;

    public function save(Task $task): void;

    public function remove(Task $task): void;
}
