<?php

namespace App\Repository;

use App\Entity\DriverProfile;
use App\Entity\DriverWithdrawalRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DriverWithdrawalRequest>
 */
class DriverWithdrawalRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DriverWithdrawalRequest::class);
    }

    /**
     * @return float
     */
    public function getPendingTotal(DriverProfile $driver): float
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.amount), 0) as pendingTotal')
            ->where('r.driver = :driver')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('driver', $driver)
            ->setParameter('statuses', [
                DriverWithdrawalRequest::STATUS_PENDING,
                DriverWithdrawalRequest::STATUS_APPROVED,
            ]);

        $result = $qb->getQuery()->getSingleResult();

        return isset($result['pendingTotal']) ? (float) $result['pendingTotal'] : 0.0;
    }

    /**
     * @return list<DriverWithdrawalRequest>
     */
    public function findRecentForDriver(DriverProfile $driver, int $limit = 20): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.driver = :driver')
            ->setParameter('driver', $driver)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
