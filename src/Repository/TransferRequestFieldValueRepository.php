<?php

namespace App\Repository;

use App\Entity\TransferRequestFieldValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferRequestFieldValue>
 */
class TransferRequestFieldValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferRequestFieldValue::class);
    }
}
