<?php


namespace App\Services;


use App\Entity\Plataforma;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class mailerServer extends AbstractController
{




    public static function enviarPagoAprobadoReserva(EntityManagerInterface $em,MailerInterface $mailer, $reserva,$urlDetalles)
    {


        $emailcontext = [
            'reserva' => $reserva,
            'idiomaPlataforma' => $reserva->getIdiomaPreferido(),
            'melink' =>$urlDetalles
        ];
        $email = (new TemplatedEmail())
            ->from(new Address('tienda@shophardware.com.ar', $em->getRepository(Plataforma::class)->find(1)->getNombre() . ' bot'))
            ->to($reserva->getEmail())
            ->subject('Pago - ' . $reserva->getBooking()->getNombre())
            ->htmlTemplate('email/pagoAprobadoReservado.html.twig')
            ->context($emailcontext);
        $mailer->send($email);


    }

}