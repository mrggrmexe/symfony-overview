<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * “Универсальный” список тикетов с фильтрами и пагинацией.
     * Удобно для /support/tickets и даже для /tickets.
     *
     * @return array{items: Ticket[], total: int, page: int, perPage: int}
     */
    public function search(
        ?string $q = null,
        ?string $status = null,
        ?string $priority = null,
        ?int $categoryId = null,
        ?int $authorId = null,
        ?int $assignedToId = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));

        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')->addSelect('c')
            ->leftJoin('t.author', 'a')->addSelect('a')
            ->leftJoin('t.assignedTo', 'asgn')->addSelect('asgn')
            ->orderBy('t.id', 'DESC');

        $q = $q !== null ? trim($q) : '';
        if ($q !== '') {
            // SQLite поддерживает LIKE, делаем простую "нижнюю" нормализацию.
            $qb->andWhere('LOWER(t.title) LIKE :q OR LOWER(t.description) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        if ($status !== null) {
            $status = strtoupper(trim($status));
            if (in_array($status, Ticket::allowedStatuses(), true)) {
                $qb->andWhere('t.status = :status')->setParameter('status', $status);
            }
        }

        if ($priority !== null) {
            $priority = strtoupper(trim($priority));
            if (in_array($priority, Ticket::allowedPriorities(), true)) {
                $qb->andWhere('t.priority = :priority')->setParameter('priority', $priority);
            }
        }

        if ($categoryId !== null && $categoryId > 0) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', $categoryId);
        }

        if ($authorId !== null && $authorId > 0) {
            $qb->andWhere('a.id = :aid')->setParameter('aid', $authorId);
        }

        if ($assignedToId !== null && $assignedToId > 0) {
            $qb->andWhere('asgn.id = :sid')->setParameter('sid', $assignedToId);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        $paginator = new Paginator($qb->getQuery(), true);

        return [
            'items' => iterator_to_array($paginator->getIterator(), false),
            'total' => count($paginator),
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Тикеты конкретного пользователя (ROLE_USER): “мои заявки”.
     * @return Ticket[]
     */
    public function findForUser(User $user, int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));

        return $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')->addSelect('c')
            ->andWhere('t.author = :u')
            ->setParameter('u', $user)
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Удобно для поддержки: тикеты по статусу.
     * @return Ticket[]
     */
    public function findByStatus(string $status, int $limit = 100): array
    {
        $status = strtoupper(trim($status));
        if (!in_array($status, Ticket::allowedStatuses(), true)) {
            $status = Ticket::STATUS_NEW;
        }
        $limit = max(1, min(1000, $limit));

        return $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')->addSelect('c')
            ->leftJoin('t.author', 'a')->addSelect('a')
            ->andWhere('t.status = :s')
            ->setParameter('s', $status)
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
