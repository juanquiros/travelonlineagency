<?php

namespace App\Repository;

use App\Entity\TransferCombo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferCombo>
 */
class TransferComboRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferCombo::class);
    }

    /**
     * @return TransferCombo[]
     */
    public function findActivos(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
