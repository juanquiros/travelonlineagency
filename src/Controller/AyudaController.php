<?php

namespace App\Controller;

use App\Entity\Plataforma;
use App\Entity\PreguntaFrecuente;
use App\Entity\TraduccionPlataforma;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AyudaController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    #[Route('/ayuda', name: 'app_ayuda')]
    public function index(Request $request): Response
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em,$request);
        $plataforma=$this->em->getRepository(Plataforma::class)->find(1);

        $titulo = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_ayuda:titulo','lenguaje'=>$idioma->getId()]);
        if(!isset($titulo) || empty($titulo)) $titulo = $this->em->getRepository(TraduccionPlataforma::class)->findOneBy(['key_name'=>'app_ayuda:titulo']);
        if(!isset($titulo) || empty($titulo)){ $titulo = "Preguntas Frecuentes";}else{$titulo = $titulo->getValue();}



        $preguntas = [];
        $aux = $this->em->getRepository(PreguntaFrecuente::class)->findAll();
        if(isset($aux) && !empty($aux)) {
            foreach ($aux as $pregunta) {
                $preguntas [] = $pregunta->getTraduccion($idioma);
            }
        }

        return $this->render('ayuda/index.html.twig', [
            'titulo' => $titulo,
            'usuario'=>$this->getUser(),
            'plataforma'=>$plataforma,
            'idiomas'=>$idiomas,
            'idiomaPlataforma'=>$idioma,
            'preguntas'=>$preguntas
        ]);
    }
}
