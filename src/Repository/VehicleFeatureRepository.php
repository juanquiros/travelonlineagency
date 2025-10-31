<?php

namespace App\Repository;

use App\Entity\VehicleFeature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleFeature>
 */
class VehicleFeatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleFeature::class);
    }

    /**
     * @return VehicleFeature[]
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('f.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCaseInsensitiveName(string $nombre): ?VehicleFeature
    {
        return $this->createQueryBuilder('f')
            ->andWhere('LOWER(f.nombre) = LOWER(:nombre)')
            ->setParameter('nombre', trim($nombre))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
