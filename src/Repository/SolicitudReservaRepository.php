<?php

namespace App\Repository;

use App\Entity\SolicitudReserva;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use mysql_xdevapi\Exception;

/**
 * @extends ServiceEntityRepository<SolicitudReserva>
 */
class SolicitudReservaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolicitudReserva::class);
    }

    //    /**
    //     * @return SolicitudReserva[] Returns an array of SolicitudReserva objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }
        //SELECT solicitud_reserva.fecha_seleccionada,(count(solicitud_reserva.id) + SUM(JSON_LENGTH(solicitud_reserva.in_charge_of))) as solicitudes FROM solicitud_reserva WHERE solicitud_reserva.booking_id =37 GROUP BY solicitud_reserva.fecha_seleccionada;
    public function solicitudesDeBooking(int $idBooking, int $idEstado, string $fecha = null): ?array
    {
        $query = null;
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s.fechaSeleccionada as fecha, (count(s.id) + SUM(JSON_LENGTH(s.inChargeOf))) as solicitudes ')
            ->andWhere('s.Booking = :idBooking')
            ->andWhere('s.estado = :idEstado');
        if (isset($fecha) && !empty($fecha) &&  strlen($fecha) >= 16){
            $datetime = \DateTime::createFromFormat( 'd-m-Y H:i', $fecha);
            if(!$datetime) return null;
                $queryBuilder->andWhere('s.fechaSeleccionada = :fechaFiltro')
                        ->setParameter('fechaFiltro',$datetime->format('Y-m-d H:i'));
        }
        $query = $queryBuilder
            ->setParameter('idBooking', $idBooking)
            ->setParameter('idEstado', $idEstado)
            ->groupBy('fecha')
            ->getQuery();
        return $query->getResult();
    }
    public function reservas(int $idBooking, int $idEstado, string $fecha = null):SolicitudReserva|array
    {
        $query = [];
        $queryBuilder = $this->createQueryBuilder('s')
            ->andWhere('s.Booking = :idBooking')
            ->andWhere('s.estado = :idEstado');
        if (isset($fecha) && !empty($fecha) &&  strlen($fecha) >= 16){
            $datetime = \DateTime::createFromFormat( 'd-m-Y H:i', $fecha);
            if(!$datetime) return [];
            $queryBuilder->andWhere('s.fechaSeleccionada = :fechaFiltro')
                ->setParameter('fechaFiltro',$datetime->format('Y-m-d H:i'));
        }
        $query = $queryBuilder
            ->setParameter('idBooking', $idBooking)
            ->setParameter('idEstado', $idEstado)
            ->getQuery();
        return $query->getResult();
    }
}
