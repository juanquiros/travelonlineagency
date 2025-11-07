<?php

namespace App\Repository;

use App\Entity\Moneda;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Moneda>
 */
class MonedaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Moneda::class);
    }

    /**
     * @return Moneda[]
     */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.habilitada = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('m.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
