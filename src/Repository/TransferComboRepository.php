<?php

namespace App\Repository;

use App\Entity\TransferCombo;
use App\Entity\TransferDestination;
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

    /**
     * @return TransferCombo[]
     */
    public function findActivosConDestinos(): array
    {
        return $this->createQueryBuilder('combo')
            ->leftJoin('combo.destinos', 'detalle')
            ->addSelect('detalle')
            ->leftJoin('detalle.destino', 'destino')
            ->addSelect('destino')
            ->leftJoin('destino.categoria', 'categoria')
            ->addSelect('categoria')
            ->leftJoin('categoria.iconDefinition', 'icono')
            ->addSelect('icono')
            ->andWhere('combo.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('combo.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TransferCombo[]
     */
    public function findActivosPorDestino(TransferDestination $destino): array
    {
        return $this->createQueryBuilder('combo')
            ->innerJoin('combo.destinos', 'detalle')
            ->addSelect('detalle')
            ->innerJoin('detalle.destino', 'destino')
            ->addSelect('destino')
            ->leftJoin('destino.categoria', 'categoria')
            ->addSelect('categoria')
            ->leftJoin('categoria.iconDefinition', 'icono')
            ->addSelect('icono')
            ->andWhere('combo.activo = :activo')
            ->andWhere('destino = :destino')
            ->setParameter('activo', true)
            ->setParameter('destino', $destino)
            ->orderBy('combo.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
