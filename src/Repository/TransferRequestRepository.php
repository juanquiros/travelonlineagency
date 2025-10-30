<?php

namespace App\Repository;

use App\Entity\TransferAssignment;
use App\Entity\TransferRequest;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferRequest>
 */
class TransferRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferRequest::class);
    }

    /**
     * @return TransferRequest[]
     */
    public function findPendientes(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.estado = :estado')
            ->setParameter('estado', TransferRequest::ESTADO_PENDIENTE)
            ->orderBy('r.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TransferRequest[]
     */
    public function findForDriverDashboard(Usuario $usuario): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.asignaciones', 'a')
            ->leftJoin('a.chofer', 'c')
            ->leftJoin('c.usuario', 'u')
            ->andWhere('r.estado IN (:estadosCapturables) OR (u = :usuario AND a.estado IN (:estados))')
            ->setParameter('usuario', $usuario)
            ->setParameter('estadosCapturables', [
                TransferRequest::ESTADO_PENDIENTE,
            ])
            ->setParameter('estados', [
                TransferAssignment::ESTADO_CAPTURADO,
                TransferAssignment::ESTADO_EN_CURSO,
            ])
            ->orderBy('r.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TransferRequest[]
     */
    public function findLatestTestimonials(int $limit = 6): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.calificacion IS NOT NULL')
            ->andWhere('r.testimonioComentario IS NOT NULL')
            ->andWhere('r.testimonioComentario <> :vacio')
            ->andWhere('r.estado = :estado')
            ->setParameter('vacio', '')
            ->setParameter('estado', TransferRequest::ESTADO_COMPLETADO)
            ->orderBy('r.testimonioCreadoEn', 'DESC')
            ->addOrderBy('r.creadoEn', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
