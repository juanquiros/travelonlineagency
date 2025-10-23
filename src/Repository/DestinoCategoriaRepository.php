<?php

namespace App\Repository;

use App\Entity\DestinoCategoria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DestinoCategoria>
 */
class DestinoCategoriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DestinoCategoria::class);
    }

    /**
     * @return DestinoCategoria[]
     */
    public function findActivas(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
