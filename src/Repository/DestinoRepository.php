<?php

namespace App\Repository;

use App\Entity\Destino;
use App\Entity\DestinoCategoria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Destino>
 */
class DestinoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Destino::class);
    }

    /**
     * @return Destino[]
     */
    public function search(?string $query, ?DestinoCategoria $categoria = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.categoria', 'c')
            ->addSelect('c')
            ->orderBy('d.nombre', 'ASC');

        if ($query) {
            $qb->andWhere('LOWER(d.nombre) LIKE :q OR LOWER(d.direccion) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($query) . '%');
        }

        if ($categoria instanceof DestinoCategoria) {
            $qb->andWhere('d.categoria = :categoria')
               ->setParameter('categoria', $categoria);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Destino[]
     */
    public function findActivos(): array
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.categoria', 'c')
            ->addSelect('c')
            ->andWhere('d.activo = true')
            ->orderBy('d.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
