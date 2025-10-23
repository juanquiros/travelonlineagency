<?php

namespace App\Controller\Admin;

use App\Entity\Destino;
use App\Entity\DestinoCategoria;
use App\Entity\Plataforma;
use App\Form\DestinoCategoriaType;
use App\Form\DestinoFilterType;
use App\Form\DestinoType;
use App\Repository\DestinoCategoriaRepository;
use App\Repository\DestinoRepository;
use App\Services\LanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/administrador/destinos')]
#[IsGranted('ROLE_ADMIN')]
class DestinoController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_admin_destino_index', methods: ['GET'])]
    public function index(Request $request, DestinoRepository $destinoRepository, DestinoCategoriaRepository $categoriaRepository): Response
    {
        $filterForm = $this->createForm(DestinoFilterType::class);
        $filterForm->submit($request->query->all(), false);

        $categoria = $filterForm->get('categoria')->getData();
        $query = $filterForm->get('q')->getData();

        $destinos = $destinoRepository->search($query, $categoria);
        $categorias = $categoriaRepository->findActivas();

        return $this->render('admin/destino_index.html.twig', array_merge(
            $this->buildContext($request, ['destinos' => true]),
            [
                'filterForm' => $filterForm->createView(),
                'destinos' => $destinos,
                'categorias' => $categorias,
            ]
        ));
    }

    #[Route('/nuevo', name: 'app_admin_destino_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $destino = new Destino();
        $form = $this->createForm(DestinoType::class, $destino);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImagen($form->get('imagenPrincipalUpload')->getData(), $destino, $slugger);
            $this->em->persist($destino);
            $this->em->flush();

            $this->addFlash('success', 'Destino creado correctamente.');

            return $this->redirectToRoute('app_admin_destino_index');
        }

        return $this->render('admin/destino_form.html.twig', array_merge(
            $this->buildContext($request, ['destinos' => true]),
            [
                'form' => $form->createView(),
                'destino' => $destino,
                'isNew' => true,
            ]
        ));
    }

    #[Route('/{id}/editar', name: 'app_admin_destino_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Destino $destino, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(DestinoType::class, $destino);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImagen($form->get('imagenPrincipalUpload')->getData(), $destino, $slugger);
            $this->em->flush();

            $this->addFlash('success', 'Destino actualizado correctamente.');

            return $this->redirectToRoute('app_admin_destino_index');
        }

        return $this->render('admin/destino_form.html.twig', array_merge(
            $this->buildContext($request, ['destinos' => true]),
            [
                'form' => $form->createView(),
                'destino' => $destino,
                'isNew' => false,
            ]
        ));
    }

    #[Route('/{id}', name: 'app_admin_destino_delete', methods: ['POST'])]
    public function delete(Request $request, Destino $destino): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_destino_' . $destino->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');

            return $this->redirectToRoute('app_admin_destino_index');
        }

        $this->em->remove($destino);
        $this->em->flush();

        $this->addFlash('success', 'Destino eliminado correctamente.');

        return $this->redirectToRoute('app_admin_destino_index');
    }

    #[Route('/categorias', name: 'app_admin_destino_categorias', methods: ['GET', 'POST'])]
    public function categorias(Request $request, DestinoCategoriaRepository $categoriaRepository): Response
    {
        $categoria = new DestinoCategoria();
        $form = $this->createForm(DestinoCategoriaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($categoria);
            $this->em->flush();

            $this->addFlash('success', 'Categoría creada correctamente.');

            return $this->redirectToRoute('app_admin_destino_categorias');
        }

        return $this->render('admin/destino_categorias.html.twig', array_merge(
            $this->buildContext($request, ['destinos' => true]),
            [
                'form' => $form->createView(),
                'categorias' => $categoriaRepository->findActivas(),
            ]
        ));
    }

    #[Route('/categorias/{id}/editar', name: 'app_admin_destino_categoria_edit', methods: ['GET', 'POST'])]
    public function editarCategoria(Request $request, DestinoCategoria $categoria): Response
    {
        $form = $this->createForm(DestinoCategoriaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Categoría actualizada correctamente.');

            return $this->redirectToRoute('app_admin_destino_categorias');
        }

        return $this->render('admin/destino_categoria_form.html.twig', array_merge(
            $this->buildContext($request, ['destinos' => true]),
            [
                'form' => $form->createView(),
                'categoria' => $categoria,
            ]
        ));
    }

    #[Route('/categorias/{id}', name: 'app_admin_destino_categoria_delete', methods: ['POST'])]
    public function eliminarCategoria(Request $request, DestinoCategoria $categoria): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_destino_categoria_' . $categoria->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');

            return $this->redirectToRoute('app_admin_destino_categorias');
        }

        if (count($categoria->getDestinos()) > 0) {
            $this->addFlash('error', 'No es posible eliminar la categoría porque tiene destinos asociados.');

            return $this->redirectToRoute('app_admin_destino_categorias');
        }

        $this->em->remove($categoria);
        $this->em->flush();

        $this->addFlash('success', 'Categoría eliminada correctamente.');

        return $this->redirectToRoute('app_admin_destino_categorias');
    }

    private function buildContext(Request $request, array $menuOverrides = []): array
    {
        $idiomas = LanguageService::getLenguajes($this->em);
        $idioma = LanguageService::getLenguaje($this->em, $request);
        $plataforma = $this->em->getRepository(Plataforma::class)->find(1);

        $menu = [
            'notificaciones' => false,
            'mensajes' => false,
            'traslados' => false,
            'reservas' => false,
            's_traslados' => false,
            's_reservas' => false,
            'configuraciones' => false,
            'dashboard' => false,
            's_preguntas' => false,
            'partners' => false,
            'balance' => false,
            'transfer_requests' => false,
            'transfer_destinations' => false,
            'transfer_combos' => false,
            'transfer_campos' => false,
            'drivers' => false,
            'destinos' => false,
        ];

        foreach ($menuOverrides as $key => $value) {
            if (array_key_exists($key, $menu)) {
                $menu[$key] = $value;
            }
        }

        return [
            'usuario' => $this->getUser(),
            'idiomas' => $idiomas,
            'idiomaPlataforma' => $idioma,
            'plataforma' => $plataforma,
            'menu' => $menu,
        ];
    }

    private function handleImagen(?UploadedFile $file, Destino $destino, SluggerInterface $slugger): void
    {
        if (!$file instanceof UploadedFile) {
            return;
        }

        $targetDirectory = $this->getParameter('img_destinos');
        if (!is_dir($targetDirectory) && !@mkdir($targetDirectory, 0o775, true) && !is_dir($targetDirectory)) {
            $this->addFlash('error', 'No se pudo crear el directorio de destino para la imagen.');

            return;
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($baseName);
        $extension = $file->guessExtension() ?: 'jpg';
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);

        try {
            $file->move($targetDirectory, $newFilename);
        } catch (FileException $exception) {
            $this->addFlash('error', 'No se pudo subir la imagen: ' . $exception->getMessage());

            return;
        }

        $destino->setImagenPrincipal($newFilename);
    }
}
