<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AutorRelatorio;
use App\Model\LivroRelatorio;
use App\Repository\LivroRepository;

/**
 * Monta o relatório de livros agrupado por autor a partir da view vw_livros_por_autor.
 */
final class RelatorioLivrosService
{
    public function __construct(
        private readonly LivroRepository $livros,
    ) {
    }

    /** @return list<AutorRelatorio> */
    public function porAutor(): array
    {
        /** @var array<int, array{nome: string, livros: list<LivroRelatorio>}> $grupos */
        $grupos = [];

        foreach ($this->livros->listarParaRelatorio() as $linha) {
            $autorId = (int) $linha['autor_id'];
            $grupos[$autorId] ??= [
                'nome' => (string) $linha['autor_nome'],
                'livros' => [],
            ];

            if ($linha['livro_id'] === null || $linha['livro_id'] === '') {
                continue;
            }

            $grupos[$autorId]['livros'][] = new LivroRelatorio(
                (string) $linha['titulo'],
                (string) $linha['editora'],
                (int) $linha['edicao'],
                (string) $linha['ano_publicacao'],
                (string) $linha['valor'],
                (string) $linha['assuntos'],
            );
        }

        return array_map(
            static fn (array $grupo): AutorRelatorio => new AutorRelatorio($grupo['nome'], $grupo['livros']),
            array_values($grupos),
        );
    }
}
