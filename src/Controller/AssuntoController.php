<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AssuntoDto;
use App\Exception\RegistroDuplicadoException;
use App\Exception\RegraDeNegocioException;
use App\Form\AssuntoType;
use App\Service\AssuntoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AssuntoController extends AbstractController
{
    #[Route('/assuntos', name: 'assunto_index', methods: ['GET'])]
    public function index(Request $request, AssuntoService $assuntos): Response
    {
        return $this->render('assunto/index.html.twig', [
            'pagina' => $assuntos->listar($request->query->getInt('pagina', 1)),
        ]);
    }

    #[Route('/assuntos/novo', name: 'assunto_novo', methods: ['GET', 'POST'])]
    public function novo(Request $request, AssuntoService $assuntos): Response
    {
        $dto = new AssuntoDto();
        $form = $this->createForm(AssuntoType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $assuntos->criar($dto);
                $this->addFlash('success', 'Assunto cadastrado.');

                return $this->redirectToRoute('assunto_index');
            } catch (RegraDeNegocioException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('assunto/form.html.twig', [
            'form' => $form,
            'titulo' => 'Novo assunto',
        ]);
    }

    #[Route('/assuntos/{id}/editar', name: 'assunto_editar', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editar(int $id, Request $request, AssuntoService $assuntos): Response
    {
        try {
            $dto = AssuntoDto::deAssunto($assuntos->buscar($id));
        } catch (RegraDeNegocioException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('assunto_index');
        }

        $form = $this->createForm(AssuntoType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $assuntos->atualizar($id, $dto);
                $this->addFlash('success', 'Assunto atualizado.');

                return $this->redirectToRoute('assunto_index');
            } catch (RegistroDuplicadoException $e) {
                $this->addFlash('danger', $e->getMessage());
            } catch (RegraDeNegocioException $e) {
                $this->addFlash('danger', $e->getMessage());

                return $this->redirectToRoute('assunto_index');
            }
        }

        return $this->render('assunto/form.html.twig', [
            'form' => $form,
            'titulo' => 'Editar assunto',
        ]);
    }

    #[Route('/assuntos/{id}/excluir', name: 'assunto_excluir', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function excluir(int $id, Request $request, AssuntoService $assuntos): Response
    {
        if (!$this->isCsrfTokenValid('assunto_excluir', $request->request->getString('_token'))) {
            return new Response('Token de segurança inválido.', Response::HTTP_FORBIDDEN);
        }

        try {
            $assuntos->excluir($id);
            $this->addFlash('success', 'Assunto excluído.');
        } catch (RegraDeNegocioException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('assunto_index');
    }
}
