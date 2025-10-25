<?php

namespace App\Repository;

use App\Entity\BootstrapIcon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BootstrapIcon>
 */
class BootstrapIconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BootstrapIcon::class);
    }

    /**
     * @return BootstrapIcon[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('icon')
            ->orderBy('icon.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
