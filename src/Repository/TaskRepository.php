<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Enum\TaskPriority;
use App\Domain\Enum\TaskStatus;
use App\Domain\Repository\TaskRepositoryInterface;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository implements TaskRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['owner' => $user], ['createdAt' => 'DESC']);
    }

    public function findByUserPaginated(User $user, int $page, int $limit, ?TaskStatus $status = null, ?TaskPriority $priority = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('t.createdAt', 'DESC');

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        if (null !== $priority) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', $priority);
        }

        $paginator = new Paginator($qb->getQuery());
        $total = $paginator->count();
        $pages = (int) ceil($total / $limit);

        if ($page < 1) {
            $page = 1;
        }

        $paginator
            ->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public function findById(int $id): ?Task
    {
        return $this->find($id);
    }

    public function save(Task $task): void
    {
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
    }

    public function remove(Task $task): void
    {
        $this->getEntityManager()->remove($task);
        $this->getEntityManager()->flush();
    }
}
