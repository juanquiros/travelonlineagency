<?php

namespace App\Controller;

use App\Entity\Lenguaje;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Cookie;
class LanguageController extends AbstractController
{
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }
    #[Route('/cambiarlenguaje', name: 'app_cahngelanguage',methods: ['POST'], options: ['expose'=>true])]
    public function app_cahngelanguage(Request $request, Lenguaje $idioma = null): Response
    {
        $response = new Response();
        $idioma = null;
        $newIdiomaId = $request->get('idiomaId');

        if(isset($newIdiomaId) && !empty($newIdiomaId)){
            $idioma = $this->em->getRepository(Lenguaje::class)->find($newIdiomaId);
        }
        if(!isset($idioma) || empty($idioma)){
            $response->setStatusCode(400);
            return $response->setContent( json_encode(['set'=>null]));
        }
        $cookie = Cookie::create('idiomaId', $idioma->getId(), time() + (365 * 24 * 60 * 60));
        $response->headers->setCookie($cookie);
        $response->setStatusCode(200);
        return $response->setContent( json_encode(['set'=>$idioma->getId(),'padtoimg'=>$this->getParameter('icon_language') . '/' .$idioma->getIcono(),'nombre'=>$idioma->getNombre()]));
    }
}
