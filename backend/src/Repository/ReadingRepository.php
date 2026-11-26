<?php

namespace App\Repository;

use App\Entity\Reading;
use App\Entity\User;
use App\Enum\ReadingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reading>
 */
class ReadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reading::class);
    }

    /**
     * @return list<Reading>
     */
    public function findForUser(User $user, ?ReadingStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->addSelect('b')
            ->innerJoin('r.book', 'b')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.updatedAt', 'DESC');

        if (null !== $status) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Reading
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }
}
