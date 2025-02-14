<?php


namespace App\Services;


use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class notificacion
{

    private const         AUTH = [
        'VAPID' => [
            'subject' => 'https://www.llegaya.com.ar', // can be a mailto: or your website address
            'publicKey' => 'BNnsx1A1k1VRV3twYiAM1onhVk2Jm_xvEoytObUTcjsHwdQsVkAetODIiFGP_F-Vow3uSIpjTuRuZJaOMlFXFfE', // (recommended) uncompressed public key P-256 encoded in Base64-URL
            'privateKey' => 'GNrsK0LS97Ekgzj9fXFATeAQyza9-7iYnIK035a1gRY', // (recommended) in fact the secret multiplier of the private key encoded in Base64-URL
        ],
    ];


    public static function enviar(string $endpoint, $mensaje, $titulo, $url)
    {


        $webPush = new WebPush(self::AUTH);
        $report = $webPush->sendOneNotification(
            Subscription::create(json_decode($endpoint, true))
            , '{"title":"' . $titulo . '" , "body":"' . $mensaje . '" , "url":"' . $url . '", "icon":"http://www.travelonlineagency.local/favicon.ico"}', ['TTL' => 5000]);
        return $report;


    }
    public static function enviarMasivo(array $usuarios, $mensaje, $titulo, $url):array
    {
        $report = [];
        if(isset($usuarios) && !empty($usuarios)){
            foreach ($usuarios as $usuario) {
                $endpoints = $usuario->getPushEndPoints();
                foreach ($endpoints as $endpoint) {
                    $aux=[];
                    $aux['usuario']=$usuario;
                    $webPush = new WebPush(self::AUTH);
                    $data = ["title"=> $titulo,
                        "body"=>$mensaje,
                        "url"=> $url,
                        "icon"=>"https://localhost:8000/favicon.ico"
                        ];
                    $aux['report'] = $webPush->sendOneNotification(
                        Subscription::create(json_decode($endpoint->getSuscripcion(), true))
                        , json_encode($data), ['TTL' => 5000]);
                    $report [] = $aux;
                }
            }
        }
        return $report;
    }
}