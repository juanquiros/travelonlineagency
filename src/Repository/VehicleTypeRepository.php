<?php

namespace App\Repository;

use App\Entity\VehicleType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleType>
 */
class VehicleTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleType::class);
    }

    /**
     * @return VehicleType[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('v.orden', 'ASC')
            ->addOrderBy('v.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
