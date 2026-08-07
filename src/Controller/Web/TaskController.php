<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Application\TaskService;
use App\Domain\Exception\TaskAccessDeniedException;
use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    #[Route('/tasks', name: 'app_task_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('task/index.html.twig', [
            'tasks' => $this->taskService->list($user),
        ]);
    }

    #[Route('/tasks/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $this->taskService->create($task, $user);

            $this->addFlash('success', 'Task created successfully.');

            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/tasks/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $task = $this->taskService->findOne($id, $user);
        } catch (TaskAccessDeniedException) {
            throw $this->createNotFoundException();
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $task = $this->taskService->findOne($id, $user);
        } catch (TaskAccessDeniedException) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TaskType::class, $task, [
            'original_due_date' => $task->getDueDate(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskService->update($task, $user);

            $this->addFlash('success', 'Task updated successfully.');

            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form,
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $task = $this->taskService->findOne($id, $user);
        } catch (TaskAccessDeniedException) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete-task-'.$task->getId(), (string) $request->request->get('_token'))) {
            $this->taskService->delete($task, $user);
            $this->addFlash('success', 'Task deleted successfully.');
        }

        return $this->redirectToRoute('app_task_index');
    }
}
