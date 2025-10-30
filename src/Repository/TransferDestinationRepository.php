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

    /**
     * @return TransferDestination[]
     */
    public function findActivosConCategoria(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.categoria', 'c')
            ->addSelect('c')
            ->leftJoin('c.iconDefinition', 'icon')
            ->addSelect('icon')
            ->andWhere('d.activo = :activo')
            ->setParameter('activo', true)
            ->orderBy('d.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TransferDestination[]
     */
    public function search(?string $query, ?int $categoriaId): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.categoria', 'c')
            ->addSelect('c')
            ->leftJoin('c.iconDefinition', 'icon')
            ->addSelect('icon')
            ->orderBy('d.nombre', 'ASC');

        if ($query) {
            $qb->andWhere('LOWER(d.nombre) LIKE :busqueda OR LOWER(d.descripcionCorta) LIKE :busqueda')
                ->setParameter('busqueda', '%' . mb_strtolower($query) . '%');
        }

        if ($categoriaId) {
            $qb->andWhere('c.id = :categoria')
                ->setParameter('categoria', $categoriaId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return TransferDestination[]
     */
    public function findDestacados(int $limit = 3): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.categoria', 'c')
            ->addSelect('c')
            ->leftJoin('c.iconDefinition', 'icon')
            ->addSelect('icon')
            ->andWhere('d.activo = :activo')
            ->andWhere('d.destacadoInicio = :destacado')
            ->setParameter('activo', true)
            ->setParameter('destacado', true)
            ->orderBy('d.ordenDestacado', 'ASC')
            ->addOrderBy('d.nombre', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
