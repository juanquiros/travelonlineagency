<?php

namespace App\Repository;

use App\Entity\TransferFormField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferFormField>
 */
class TransferFormFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferFormField::class);
    }

    /**
     * @return TransferFormField[]
     */
    public function findForForm(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.orden', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
