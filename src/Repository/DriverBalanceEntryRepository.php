<?php

namespace App\Repository;

use App\Entity\DriverBalanceEntry;
use App\Entity\DriverProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DriverBalanceEntry>
 */
class DriverBalanceEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DriverBalanceEntry::class);
    }

    /**
     * @return array{credits: float, debits: float, balance: float}
     */
    public function getTotals(DriverProfile $driver): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COALESCE(SUM(CASE WHEN e.direction = :credit THEN e.amount ELSE 0 END), 0) as creditTotal')
            ->addSelect('COALESCE(SUM(CASE WHEN e.direction = :debit THEN e.amount ELSE 0 END), 0) as debitTotal')
            ->where('e.driver = :driver')
            ->setParameter('driver', $driver)
            ->setParameter('credit', DriverBalanceEntry::DIRECTION_CREDIT)
            ->setParameter('debit', DriverBalanceEntry::DIRECTION_DEBIT);

        $result = $qb->getQuery()->getSingleResult();

        $credits = isset($result['creditTotal']) ? (float) $result['creditTotal'] : 0.0;
        $debits = isset($result['debitTotal']) ? (float) $result['debitTotal'] : 0.0;

        return [
            'credits' => $credits,
            'debits' => $debits,
            'balance' => $credits - $debits,
        ];
    }

    /**
     * @return array{credits: float, debits: float, balance: float}
     */
    public function getTotalsForAll(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COALESCE(SUM(CASE WHEN e.direction = :credit THEN e.amount ELSE 0 END), 0) as creditTotal')
            ->addSelect('COALESCE(SUM(CASE WHEN e.direction = :debit THEN e.amount ELSE 0 END), 0) as debitTotal')
            ->setParameter('credit', DriverBalanceEntry::DIRECTION_CREDIT)
            ->setParameter('debit', DriverBalanceEntry::DIRECTION_DEBIT);

        $result = $qb->getQuery()->getSingleResult();

        $credits = isset($result['creditTotal']) ? (float) $result['creditTotal'] : 0.0;
        $debits = isset($result['debitTotal']) ? (float) $result['debitTotal'] : 0.0;

        return [
            'credits' => $credits,
            'debits' => $debits,
            'balance' => $credits - $debits,
        ];
    }

    /**
     * @return list<DriverBalanceEntry>
     */
    public function findRecentForDriver(DriverProfile $driver, int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.driver = :driver')
            ->setParameter('driver', $driver)
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<DriverBalanceEntry>
     */
    public function findRecentGlobal(int $limit = 25): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.driver', 'd')->addSelect('d')
            ->leftJoin('d.usuario', 'u')->addSelect('u')
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
