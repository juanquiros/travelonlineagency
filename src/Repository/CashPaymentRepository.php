<?php

namespace App\Repository;

use App\Entity\CashPayment;
use App\Entity\SolicitudReserva;
use App\Entity\TransferRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CashPayment>
 */
class CashPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CashPayment::class);
    }

    public function findLatestForReservation(SolicitudReserva $reserva): ?CashPayment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.solicitudReserva = :reserva')
            ->setParameter('reserva', $reserva)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestForTransfer(TransferRequest $transfer): ?CashPayment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.transferRequest = :transfer')
            ->setParameter('transfer', $transfer)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
