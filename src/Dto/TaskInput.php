<?php

declare(strict_types=1);

namespace App\Dto;

use App\Domain\Enum\TaskPriority;
use App\Domain\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class TaskInput
{
    #[Assert\NotBlank(message: 'Task title is required.')]
    #[Assert\Length(max: 255)]
    public string $title;

    public ?string $description = null;

    public TaskStatus $status = TaskStatus::Todo;

    public TaskPriority $priority = TaskPriority::Medium;

    #[Assert\GreaterThanOrEqual('today', message: 'Due date cannot be in the past.')]
    public ?\DateTimeImmutable $dueDate = null;
}
