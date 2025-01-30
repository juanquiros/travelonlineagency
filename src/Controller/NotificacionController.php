<?php

namespace App\Controller;

use App\Entity\PushEndPoint;
use App\Service\notificacion;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\VAPID;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NotificacionController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }



    #[Route('/notificacion/registrar/suscripcion', name: 'app_notificacion_registrar', methods: ['POST'],options: ['expose'=>true])]
    public function app_notificacion_registrar(Request $request): Response
    {
        $usuario=$this->getUser();
        if( isset($usuario) && !empty($usuario)  ) {
            $suscripcion = $request->request->get('suscripcion');
            $suscripcionObj = $this->em->getRepository(PushEndPoint::class)->findOneBy(['usuario' => $usuario->getId(), 'suscripcion' => $suscripcion]);
            if (!isset($suscripcionObj) || empty($suscripcionObj)) {
                $suscripcionObj = new PushEndPoint();
                $suscripcionObj->setSuscripcion($suscripcion);
                $suscripcionObj->setUsuario($usuario);
                $this->em->persist($suscripcionObj);
                $this->em->flush();
            }
        }

        return new JsonResponse(['message'=>'success'],200);
    }

    /* #[Route('/notificacion/admin', name: 'app_crear_webapp')]
     public function app_crear_webapp(): Response
     {

         $credencialesVAPID = json_encode(VAPID::createVapidKeys());

          *  "publicKey" => "BNnsx1A1k1VRV3twYiAM1onhVk2Jm_xvEoytObUTcjsHwdQsVkAetODIiFGP_F-Vow3uSIpjTuRuZJaOMlFXFfE"
          *  "privateKey" => "GNrsK0LS97Ekgzj9fXFATeAQyza9-7iYnIK035a1gRY"
          *
          * {
          * "endpoint":"https://updates.push.services.mozilla.com/wpush/v2/gAAAAABmTesS0o7_1VExC-a9pzvCAALqsV9Dms_lRgntu8OWcd8CPpetK4jmTqqkrfFAkbcaEMhCkhMIBbB7LhI4ZMBLqul0COddW11A1_j8EJH6GKbmp5fiKQaoKRoC4lQsxXj-zB16pVOFevZLdq0CMmsxbrfnau8YzQycZRP7BY74dNPidIs",
          * "expirationTime":null,
          * "keys":{"auth":"HngSMPsmsV15mJHKgCvv1g","p256dh":"BGCKcEilljG_FaGqG9wHvC2v2Xvhoql6iq0escQmX9xA98Fw-tWY3bPlYNS4RFuYky7sN39ygV1pyJOwESQMHIk"}}
          *
          *
        return new JsonResponse($credencialesVAPID,200);
    }*/
}
