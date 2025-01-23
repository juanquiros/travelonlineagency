<?php

namespace App\Repository;

use App\Entity\Lenguaje;
use App\Entity\TraduccionEstado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TraduccionEstado>
 */
class TraduccionEstadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TraduccionEstado::class);
    }

    //    /**
    //     * @return TraduccionEstado[] Returns an array of TraduccionEstado objects
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

        public function buscartraducciones(Lenguaje $lenguaje): ?TraduccionEstado
        {
            $traducciones = null;
            if(isset($lenguaje) && !empty($lenguaje)){
                $traducciones = $this->createQueryBuilder('t')
                    ->andWhere('t.lenguaje = :val')
                    ->setParameter('val', $lenguaje->getId())
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getFirstResult()
                ;
            }
            return $traducciones;
        }
}
