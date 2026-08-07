<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postRemove)]
final class TaskLifecycleSubscriber
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Task) {
            $this->logger->info('Task created', [
                'id' => $entity->getId(),
                'title' => $entity->getTitle(),
            ]);
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Task) {
            $this->logger->info('Task deleted', [
                'id' => $entity->getId(),
                'title' => $entity->getTitle(),
            ]);
        }
    }
}
