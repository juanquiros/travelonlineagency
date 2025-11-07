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

    /**
     * @return string[]
     */
    public function findDistinctVehicleTypes(): array
    {
        $results = $this->createQueryBuilder('d')
            ->leftJoin('d.vehicleType', 'vt')
            ->select('DISTINCT COALESCE(vt.nombre, d.tipoVehiculo) AS tipo')
            ->andWhere('(d.tipoVehiculo IS NOT NULL AND d.tipoVehiculo <> :vacio) OR vt.id IS NOT NULL')
            ->setParameter('vacio', '')
            ->orderBy('tipo', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => (string) $row['tipo'], $results);
    }
}
