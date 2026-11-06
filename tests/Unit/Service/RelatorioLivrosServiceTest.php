<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\LivroRepository;
use App\Service\RelatorioLivrosService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RelatorioLivrosServiceTest extends TestCase
{
    private LivroRepository&MockObject $livros;
    private RelatorioLivrosService $service;

    protected function setUp(): void
    {
        $this->livros = $this->createMock(LivroRepository::class);
        $this->service = new RelatorioLivrosService($this->livros);
    }

    public function testAgrupaLivrosPorAutorEMantemAutorSemLivro(): void
    {
        $this->livros->expects(self::once())->method('listarParaRelatorio')->willReturn([
            $this->linha(1, 'Machado de Assis', 10, 'Dom Casmurro', 'Garnier', 1, '1899', '49.90', 'Romance'),
            $this->linha(1, 'Machado de Assis', 11, 'Quincas Borba', 'Garnier', 1, '1891', '32.50', ''),
            $this->linha(2, 'Sem Obras', null, null, null, null, null, null, ''),
        ]);

        $grupos = $this->service->porAutor();

        self::assertCount(2, $grupos);
        self::assertSame('Machado de Assis', $grupos[0]->nome);
        self::assertCount(2, $grupos[0]->livros);
        self::assertSame('Dom Casmurro', $grupos[0]->livros[0]->titulo);
        self::assertSame(1, $grupos[0]->livros[0]->edicao);
        self::assertSame('49.90', $grupos[0]->livros[0]->valor);
        self::assertSame('Romance', $grupos[0]->livros[0]->assuntos);
        self::assertSame('Quincas Borba', $grupos[0]->livros[1]->titulo);
        self::assertSame('', $grupos[0]->livros[1]->assuntos);
        self::assertSame('Sem Obras', $grupos[1]->nome);
        self::assertSame([], $grupos[1]->livros);
    }

    public function testLivroComDoisAutoresApareceEmCadaGrupo(): void
    {
        $this->livros->expects(self::once())->method('listarParaRelatorio')->willReturn([
            $this->linha(1, 'Autor A', 10, 'Dom Casmurro', 'Garnier', 2, '1900', '10.00', 'Drama'),
            $this->linha(2, 'Autor B', 10, 'Dom Casmurro', 'Garnier', 2, '1900', '10.00', 'Drama'),
        ]);

        $grupos = $this->service->porAutor();

        self::assertCount(2, $grupos);
        self::assertSame('Dom Casmurro', $grupos[0]->livros[0]->titulo);
        self::assertSame('Dom Casmurro', $grupos[1]->livros[0]->titulo);
    }

    public function testSemLinhasDevolveListaVazia(): void
    {
        $this->livros->expects(self::once())->method('listarParaRelatorio')->willReturn([]);

        self::assertSame([], $this->service->porAutor());
    }

    /** @return array<string, mixed> */
    private function linha(
        int $autorId,
        string $autorNome,
        ?int $livroId,
        ?string $titulo,
        ?string $editora,
        ?int $edicao,
        ?string $ano,
        ?string $valor,
        string $assuntos,
    ): array {
        return [
            'autor_id' => $autorId,
            'autor_nome' => $autorNome,
            'livro_id' => $livroId,
            'titulo' => $titulo,
            'editora' => $editora,
            'edicao' => $edicao,
            'ano_publicacao' => $ano,
            'valor' => $valor,
            'assuntos' => $assuntos,
        ];
    }
}
