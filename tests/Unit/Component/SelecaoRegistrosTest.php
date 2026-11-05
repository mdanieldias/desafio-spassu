<?php

declare(strict_types=1);

namespace App\Tests\Unit\Component;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Repository\AssuntoRepository;
use App\Repository\AutorRepository;
use App\Twig\Components\SelecaoRegistros;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SelecaoRegistrosTest extends TestCase
{
    private AutorRepository&MockObject $autores;
    private AssuntoRepository&MockObject $assuntos;
    private SelecaoRegistros $componente;

    protected function setUp(): void
    {
        $this->autores = $this->createMock(AutorRepository::class);
        $this->assuntos = $this->createMock(AssuntoRepository::class);
        $this->componente = new SelecaoRegistros($this->autores, $this->assuntos);
    }

    public function testAdicionarAutorIncluiOIdELimpaAEscolha(): void
    {
        $this->autores->expects(self::once())->method('findBy')->with([], ['nome' => 'ASC'])->willReturn([$this->autor(3, 'Machado de Assis')]);
        $this->assuntos->expects(self::never())->method('findBy');
        $this->componente->tipo = 'autor';
        $this->componente->proximoId = '3';

        $this->componente->adicionar();

        self::assertSame([3], $this->componente->selecionados);
        self::assertSame('', $this->componente->proximoId);
    }

    public function testAdicionarNaoRepeteOMesmoId(): void
    {
        $this->autores->expects(self::exactly(2))->method('findBy')->willReturn([$this->autor(3, 'Machado de Assis')]);
        $this->assuntos->expects(self::never())->method('findBy');
        $this->componente->tipo = 'autor';
        $this->componente->proximoId = '3';

        $this->componente->adicionar();
        $this->componente->proximoId = '3';
        $this->componente->adicionar();

        self::assertSame([3], $this->componente->selecionados);
    }

    public function testAdicionarIgnoraIdDesconhecido(): void
    {
        $this->autores->expects(self::once())->method('findBy')->willReturn([$this->autor(3, 'Machado de Assis')]);
        $this->assuntos->expects(self::never())->method('findBy');
        $this->componente->tipo = 'autor';
        $this->componente->proximoId = '9';

        $this->componente->adicionar();

        self::assertSame([], $this->componente->selecionados);
        self::assertSame('9', $this->componente->proximoId);
    }

    public function testAdicionarIgnoraEscolhaVazia(): void
    {
        $this->autores->expects(self::never())->method('findBy');
        $this->assuntos->expects(self::never())->method('findBy');
        $this->componente->tipo = 'autor';

        $this->componente->adicionar();

        self::assertSame([], $this->componente->selecionados);
    }

    public function testAdicionarAssuntoUsaAListaDeAssuntos(): void
    {
        $this->assuntos->expects(self::once())->method('findBy')->with([], ['descricao' => 'ASC'])->willReturn([$this->assunto(4, 'Romance')]);
        $this->autores->expects(self::never())->method('findBy');
        $this->componente->tipo = 'assunto';
        $this->componente->proximoId = '4';

        $this->componente->adicionar();

        self::assertSame([4], $this->componente->selecionados);
    }

    public function testRemoverTiraOIdDaSelecao(): void
    {
        $this->autores->expects(self::never())->method('findBy');
        $this->assuntos->expects(self::never())->method('findBy');
        $this->componente->selecionados = [3, 5];

        $this->componente->remover('3');

        self::assertSame([5], $this->componente->selecionados);
    }

    private function autor(int $id, string $nome): Autor
    {
        $autor = new Autor($nome);
        (new \ReflectionProperty(Autor::class, 'id'))->setValue($autor, $id);

        return $autor;
    }

    private function assunto(int $id, string $descricao): Assunto
    {
        $assunto = new Assunto($descricao);
        (new \ReflectionProperty(Assunto::class, 'id'))->setValue($assunto, $id);

        return $assunto;
    }
}
