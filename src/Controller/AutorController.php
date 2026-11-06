<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AutorDto;
use App\Exception\RegraDeNegocioException;
use App\Form\AutorType;
use App\Service\AutorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AutorController extends AbstractController
{
    #[Route('/autores', name: 'autor_index', methods: ['GET'])]
    public function index(Request $request, AutorService $autores): Response
    {
        return $this->render('autor/index.html.twig', [
            'pagina' => $autores->listar($request->query->getInt('pagina', 1)),
        ]);
    }

    #[Route('/autores/novo', name: 'autor_novo', methods: ['GET', 'POST'])]
    public function novo(Request $request, AutorService $autores): Response
    {
        $dto = new AutorDto();
        $form = $this->createForm(AutorType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $autores->criar($dto);
            $this->addFlash('success', 'Autor cadastrado.');

            return $this->redirectToRoute('autor_index');
        }

        return $this->render('autor/form.html.twig', [
            'form' => $form,
            'titulo' => 'Novo autor',
        ]);
    }

    #[Route('/autores/{id}/editar', name: 'autor_editar', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editar(int $id, Request $request, AutorService $autores): Response
    {
        try {
            $dto = AutorDto::deAutor($autores->buscar($id));
        } catch (RegraDeNegocioException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('autor_index');
        }

        $form = $this->createForm(AutorType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $autores->atualizar($id, $dto);
            } catch (RegraDeNegocioException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->redirectToRoute('autor_index');
            }

            $this->addFlash('success', 'Autor atualizado.');

            return $this->redirectToRoute('autor_index');
        }

        return $this->render('autor/form.html.twig', [
            'form' => $form,
            'titulo' => 'Editar autor',
        ]);
    }

    #[Route('/autores/{id}/excluir', name: 'autor_excluir', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function excluir(int $id, Request $request, AutorService $autores): Response
    {
        if (!$this->isCsrfTokenValid('autor_excluir', $request->request->getString('_token'))) {
            return new Response('Token de segurança inválido.', Response::HTTP_FORBIDDEN);
        }

        try {
            $autores->excluir($id);
            $this->addFlash('success', 'Autor excluído.');
        } catch (RegraDeNegocioException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('autor_index');
    }
}
