<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Application\TaskService;
use App\Domain\Exception\TaskAccessDeniedException;
use App\Dto\TaskInput;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/api/tasks', name: 'api_task_', format: 'json')]
final class TaskApiController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $tasks = $this->taskService->list($user);

        return $this->json($tasks, context: ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $task = $this->taskService->findOne($id, $user);
        } catch (TaskAccessDeniedException) {
            return $this->json([
                'type' => 'https://tools.ietf.org/html/rfc9110#section-15.5.5',
                'title' => 'Not Found',
                'status' => 404,
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($task, context: ['groups' => 'task:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] TaskInput $input): JsonResponse
    {
        $task = $this->dtoToTask($input);

        /** @var User $user */
        $user = $this->getUser();
        $this->taskService->create($task, $user);

        return $this->json($task, Response::HTTP_CREATED, context: ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] TaskInput $input): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $task = $this->taskService->findOne($id, $user);
        } catch (TaskAccessDeniedException) {
            return $this->json([
                'type' => 'https://tools.ietf.org/html/rfc9110#section-15.5.5',
                'title' => 'Not Found',
                'status' => 404,
            ], Response::HTTP_NOT_FOUND);
        }

        $this->applyDtoToTask($input, $task);
        $this->taskService->update($task, $user);

        return $this->json($task, context: ['groups' => 'task:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $task = $this->taskService->findOne($id, $user);
        } catch (TaskAccessDeniedException) {
            return $this->json([
                'type' => 'https://tools.ietf.org/html/rfc9110#section-15.5.5',
                'title' => 'Not Found',
                'status' => 404,
            ], Response::HTTP_NOT_FOUND);
        }

        $this->taskService->delete($task, $user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function dtoToTask(TaskInput $input): Task
    {
        $task = new Task();
        $this->applyDtoToTask($input, $task);

        return $task;
    }

    private function applyDtoToTask(TaskInput $input, Task $task): void
    {
        $task->setTitle($input->title);
        $task->setDescription($input->description);
        $task->setStatus($input->status);
        $task->setPriority($input->priority);
        $task->setDueDate($input->dueDate);
    }
}
