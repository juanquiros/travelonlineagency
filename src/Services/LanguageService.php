<?php

namespace App\Services;
use App\Entity\Lenguaje;
use App\Entity\Plataforma;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class LanguageService extends AbstractController
{

    public static function getLenguaje(EntityManagerInterface $em, Request $request): Lenguaje
    {
        $idiomaId = $request->cookies->get('idiomaId');
        $idioma=null;
        if(isset($idiomaId) && !empty($idiomaId)) {
            $idioma = $em->getRepository(Lenguaje::class)->find($idiomaId);
        }
        if(!isset($idioma) || empty($idioma)) {
            $idioma = $em->getRepository(Plataforma::class)->find(1)->getLanguageDef();
        }
        return $idioma;
    }

    public static function getLenguajes(EntityManagerInterface $em):array
    {
        return $em->getRepository(Lenguaje::class)->findBy(['habilitado' => true]);
    }


}