<?php

namespace App\Repository;

use App\Entity\TransferDestinationCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferDestinationCategory>
 */
class TransferDestinationCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferDestinationCategory::class);
    }

    /**
     * @return TransferDestinationCategory[]
     */
    public function findWithActiveDestinations(): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.destinos', 'd')
            ->andWhere('d.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('c.nombre', 'ASC')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }
}
