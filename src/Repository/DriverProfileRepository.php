<?php

namespace App\Repository;

use App\Entity\DriverProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DriverProfile>
 */
class DriverProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DriverProfile::class);
    }

    /**
     * @return DriverProfile[]
     */
    public function findPendientes(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.aprobado = :aprobado')
            ->setParameter('aprobado', false)
            ->orderBy('d.creadoEn', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
