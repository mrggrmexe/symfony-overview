<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Быстрый поиск по email (для админки/демо).
     * @return User[]
     */
    public function searchByEmail(string $q, int $limit = 20): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(200, $limit));

        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($q).'%')
            ->setMaxResults($limit)
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
