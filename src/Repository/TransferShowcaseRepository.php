<?php

namespace App\Repository;

use App\Entity\TransferShowcase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferShowcase>
 */
class TransferShowcaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferShowcase::class);
    }

    /**
     * @return TransferShowcase[]
     */
    public function findDestacados(int $limit = 3): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.destacado = :destacado')
            ->setParameter('destacado', true)
            ->orderBy('s.posicion', 'ASC')
            ->addOrderBy('s.creadoEn', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
