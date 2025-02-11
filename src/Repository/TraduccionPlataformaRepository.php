<?php

namespace App\Repository;

use App\Entity\Lenguaje;
use App\Entity\TraduccionPlataforma;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TraduccionPlataforma>
 */
class TraduccionPlataformaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TraduccionPlataforma::class);
    }

    //    /**
    //     * @return TraduccionPlataforma[] Returns an array of TraduccionPlataforma objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

        public function buscarTraduccion($key,Lenguaje $lenguaje): ?TraduccionPlataforma
        {
            $result =  $this->createQueryBuilder('t')
                ->where('(t.key_name = :key and t.lenguaje = :len)')
                ->setParameter('key', $key  )
                ->setParameter('len', $lenguaje->getId())->getQuery()->getOneOrNullResult();
            if (!isset($result) || empty($result)) $result = $this->createQueryBuilder('t')
                ->where('t.key_name = :key')
                ->setParameter('key', $key  )
                ->getQuery()
                ->getOneOrNullResult();

            return $result;

        }
}
