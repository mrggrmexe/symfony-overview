<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
final class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return Comment[]
     */
    public function findByTicket(Ticket $ticket, int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));

        return $this->createQueryBuilder('c')
            ->andWhere('c.ticket = :t')
            ->setParameter('t', $ticket)
            ->orderBy('c.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
