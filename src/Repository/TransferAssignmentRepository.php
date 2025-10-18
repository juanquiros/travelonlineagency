<?php

namespace App\Repository;

use App\Entity\TransferAssignment;
use App\Entity\TransferRequest;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransferAssignment>
 */
class TransferAssignmentRepository extends ServiceEntityRepository
{
    public const ESTADO_CAPTURADO = TransferAssignment::ESTADO_CAPTURADO;
    public const ESTADO_EN_CURSO = TransferAssignment::ESTADO_EN_CURSO;
    public const ESTADO_COMPLETADO = TransferAssignment::ESTADO_COMPLETADO;
    public const ESTADO_CANCELADO = TransferAssignment::ESTADO_CANCELADO;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferAssignment::class);
    }

    /**
     * @return TransferAssignment[]
     */
    public function findActivosParaChofer(Usuario $usuario): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.chofer', 'c')
            ->join('c.usuario', 'u')
            ->join('a.solicitud', 's')
            ->andWhere('u = :usuario')
            ->andWhere('a.estado IN (:estados)')
            ->setParameter('usuario', $usuario)
            ->setParameter('estados', [
                self::ESTADO_CAPTURADO,
                self::ESTADO_EN_CURSO,
            ])
            ->orderBy('s.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function contarActivasPorSolicitud(TransferRequest $solicitud): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.solicitud = :solicitud')
            ->andWhere('a.estado IN (:estadosActivos)')
            ->setParameter('solicitud', $solicitud)
            ->setParameter('estadosActivos', [
                self::ESTADO_CAPTURADO,
                self::ESTADO_EN_CURSO,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
