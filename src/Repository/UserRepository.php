<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Repository\UserRepositoryInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower($identifier)]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower($email)]);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
