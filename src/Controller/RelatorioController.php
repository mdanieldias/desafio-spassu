<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\RelatorioLivrosService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RelatorioController extends AbstractController
{
    #[Route('/relatorio', name: 'relatorio_livros', methods: ['GET'])]
    public function livrosPorAutor(RelatorioLivrosService $relatorio): Response
    {
        $html = $this->renderView('relatorio/livros_por_autor.html.twig', [
            'autores' => $relatorio->porAutor(),
            'geradoEm' => new \DateTimeImmutable(),
        ]);

        $opcoes = new Options();
        $opcoes->setDefaultFont('DejaVu Sans');
        $opcoes->setIsRemoteEnabled(false);

        $pdf = new Dompdf($opcoes);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        $resposta = new Response($pdf->output());
        $resposta->headers->set('Content-Type', 'application/pdf');
        $resposta->headers->set('Content-Disposition', 'inline; filename="livros-por-autor.pdf"');
        $resposta->headers->set('Cache-Control', 'private, no-store');

        return $resposta;
    }
}
