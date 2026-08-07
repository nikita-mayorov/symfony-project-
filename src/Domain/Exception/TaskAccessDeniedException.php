<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class TaskAccessDeniedException extends \RuntimeException
{
    public function __construct(int $taskId)
    {
        parent::__construct(\sprintf('Access denied to task #%d.', $taskId));
    }
}
