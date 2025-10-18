<?php

namespace App\Repository;

use App\Entity\TransferDestination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferDestination>
 */
class TransferDestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferDestination::class);
    }

    /**
     * @return TransferDestination[]
     */
    public function findActivos(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('d.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
