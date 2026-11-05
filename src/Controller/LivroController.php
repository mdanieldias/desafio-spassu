<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LivroDto;
use App\Exception\RegistroDuplicadoException;
use App\Exception\RegraDeNegocioException;
use App\Form\LivroType;
use App\Service\LivroService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LivroController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('livro_index');
    }

    #[Route('/livros', name: 'livro_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('livro/index.html.twig');
    }

    #[Route('/livros/novo', name: 'livro_novo', methods: ['GET', 'POST'])]
    public function novo(Request $request, LivroService $livros): Response
    {
        $dto = new LivroDto();
        $form = $this->createForm(LivroType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $livros->criar($dto);
                $this->addFlash('success', 'Livro cadastrado.');

                return $this->redirectToRoute('livro_index');
            } catch (RegraDeNegocioException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('livro/form.html.twig', [
            'form' => $form,
            'titulo' => 'Novo livro',
        ]);
    }

    #[Route('/livros/{id}/editar', name: 'livro_editar', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editar(int $id, Request $request, LivroService $livros): Response
    {
        try {
            $dto = LivroDto::deLivro($livros->buscar($id));
        } catch (RegraDeNegocioException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('livro_index');
        }

        $form = $this->createForm(LivroType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $livros->atualizar($id, $dto);
                $this->addFlash('success', 'Livro atualizado.');

                return $this->redirectToRoute('livro_index');
            } catch (RegistroDuplicadoException $e) {
                $this->addFlash('danger', $e->getMessage());
            } catch (RegraDeNegocioException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->redirectToRoute('livro_index');
            }
        }

        return $this->render('livro/form.html.twig', [
            'form' => $form,
            'titulo' => 'Editar livro',
        ]);
    }

    #[Route('/livros/{id}/excluir', name: 'livro_excluir', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function excluir(int $id, Request $request, LivroService $livros): Response
    {
        if (!$this->isCsrfTokenValid('livro_excluir', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }

        try {
            $livros->excluir($id);
            $this->addFlash('success', 'Livro excluído.');
        } catch (RegraDeNegocioException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('livro_index');
    }
}
