<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\LivroDto;
use App\Entity\Autor;
use App\Entity\Livro;
use App\Exception\RegistroDuplicadoException;
use App\Exception\RegistroNaoEncontradoException;
use App\Repository\LivroRepository;
use App\Service\LivroService;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LivroServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LivroRepository&MockObject $livros;
    private LivroService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->livros = $this->createMock(LivroRepository::class);
        $this->service = new LivroService($this->em, $this->livros);
    }

    public function testCriarPersisteELivroComOsDadosDoDto(): void
    {
        $autor = new Autor('Machado de Assis');
        $dto = $this->dtoValido([$autor]);

        $this->livros->expects(self::once())->method('buscarDuplicado')->willReturn(null);
        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(Livro::class));
        $this->em->expects(self::once())->method('flush');

        $livro = $this->service->criar($dto);

        self::assertSame('Dom Casmurro', $livro->getTitulo());
        self::assertSame('Garnier', $livro->getEditora());
        self::assertSame(1, $livro->getEdicao());
        self::assertSame('1899', $livro->getAnoPublicacao());
        self::assertSame('49.90', $livro->getValor());
        self::assertCount(1, $livro->getAutores());
        self::assertTrue($livro->getAutores()->contains($autor));
    }

    public function testCriarNaoDuplicaAutorRepetidoNoDto(): void
    {
        $autor = new Autor('Machado de Assis');
        $this->livros->expects(self::once())->method('buscarDuplicado')->willReturn(null);
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $livro = $this->service->criar($this->dtoValido([$autor, $autor]));

        self::assertCount(1, $livro->getAutores());
    }

    public function testCriarTraduzViolacaoDeUnicidadeEmExcecaoDeDominio(): void
    {
        $this->livros->expects(self::once())->method('buscarDuplicado')->willReturn(null);
        $this->em->expects(self::once())->method('flush')->willThrowException(
            new UniqueConstraintViolationException(new FakeDriverException('duplicate key'), null),
        );

        $this->expectException(RegistroDuplicadoException::class);
        $this->expectExceptionMessage('Dom Casmurro');

        $this->service->criar($this->dtoValido([new Autor('Machado de Assis')]));
    }

    public function testCriarRecusaTituloEditoraEEdicaoRepetidos(): void
    {
        $existente = new Livro('Dom Casmurro', 'Garnier', 1, '1899', '49.90');
        (new \ReflectionProperty(Livro::class, 'id'))->setValue($existente, 9);
        $this->livros->expects(self::once())->method('buscarDuplicado')->willReturn($existente);
        $this->em->expects(self::never())->method('persist');

        $this->expectException(RegistroDuplicadoException::class);
        $this->expectExceptionMessage('Dom Casmurro');

        $this->service->criar($this->dtoValido([new Autor('Machado de Assis')]));
    }

    public function testCriarFalhaQuandoDtoNaoFoiValidado(): void
    {
        $this->livros->expects(self::never())->method('buscarDuplicado');
        $this->em->expects(self::never())->method('persist');
        $this->expectException(\InvalidArgumentException::class);

        $this->service->criar(new LivroDto());
    }

    public function testAtualizarAlteraOsDadosDoLivroExistente(): void
    {
        $livro = new Livro('Antigo', 'Editora X', 1, '2000', '10.00');
        (new \ReflectionProperty(Livro::class, 'id'))->setValue($livro, 7);
        $this->em->expects(self::once())->method('find')->with(Livro::class, 7)->willReturn($livro);
        $this->livros->expects(self::once())->method('buscarDuplicado')->willReturn(null);
        $this->em->expects(self::once())->method('flush');

        $resultado = $this->service->atualizar(7, $this->dtoValido([new Autor('Machado de Assis')]));

        self::assertSame($livro, $resultado);
        self::assertSame('Dom Casmurro', $livro->getTitulo());
        self::assertSame('49.90', $livro->getValor());
    }

    public function testAtualizarRecusaChaveDeOutroLivro(): void
    {
        $livro = new Livro('Antigo', 'Editora X', 1, '2000', '10.00');
        (new \ReflectionProperty(Livro::class, 'id'))->setValue($livro, 7);
        $outro = new Livro('Dom Casmurro', 'Garnier', 1, '1899', '49.90');
        (new \ReflectionProperty(Livro::class, 'id'))->setValue($outro, 9);

        $this->em->expects(self::once())->method('find')->willReturn($livro);
        $this->livros->expects(self::once())->method('buscarDuplicado')->willReturn($outro);
        $this->em->expects(self::never())->method('flush');

        $this->expectException(RegistroDuplicadoException::class);

        $this->service->atualizar(7, $this->dtoValido([new Autor('Machado de Assis')]));
    }

    public function testAtualizarPermiteManterAPropriaChave(): void
    {
        $livro = new Livro('Dom Casmurro', 'Garnier', 1, '1899', '10.00');
        (new \ReflectionProperty(Livro::class, 'id'))->setValue($livro, 7);
        $this->em->expects(self::once())->method('find')->willReturn($livro);
        $this->livros->expects(self::once())->method('buscarDuplicado')->willReturn($livro);
        $this->em->expects(self::once())->method('flush');

        $resultado = $this->service->atualizar(7, $this->dtoValido([new Autor('Machado de Assis')]));

        self::assertSame('49.90', $resultado->getValor());
    }

    public function testAtualizarLivroInexistenteLancaNaoEncontrado(): void
    {
        $this->livros->expects(self::never())->method('buscarDuplicado');
        $this->em->expects(self::once())->method('find')->willReturn(null);

        $this->expectException(RegistroNaoEncontradoException::class);

        $this->service->atualizar(999, $this->dtoValido([new Autor('X')]));
    }

    public function testListarDelegaABuscaDoRepositorio(): void
    {
        $this->em->expects(self::never())->method('flush');
        $this->livros->expects(self::once())->method('buscarPorTermo')->with('dom')->willReturn([]);

        self::assertSame([], $this->service->listar('dom'));
    }

    public function testExcluirRemoveOLivro(): void
    {
        $livro = new Livro('Dom Casmurro', 'Garnier', 1, '1899', '49.90');
        $this->livros->expects(self::never())->method('buscarDuplicado');
        $this->em->method('find')->willReturn($livro);

        $this->em->expects(self::once())->method('remove')->with($livro);
        $this->em->expects(self::once())->method('flush');

        $this->service->excluir(1);
    }

    public function testExcluirLivroInexistenteLancaNaoEncontrado(): void
    {
        $this->livros->expects(self::never())->method('buscarDuplicado');
        $this->em->method('find')->willReturn(null);
        $this->em->expects(self::never())->method('remove');

        $this->expectException(RegistroNaoEncontradoException::class);

        $this->service->excluir(999);
    }

    /** @param list<Autor> $autores */
    private function dtoValido(array $autores): LivroDto
    {
        $dto = new LivroDto();
        $dto->titulo = 'Dom Casmurro';
        $dto->editora = 'Garnier';
        $dto->edicao = 1;
        $dto->anoPublicacao = '1899';
        $dto->valor = '49.90';
        $dto->autores = $autores;

        return $dto;
    }
}

/** Dublê mínimo: Driver\Exception é uma interface que estende Throwable, então não dá para usar createMock. */
final class FakeDriverException extends \Exception implements DriverException
{
    public function getSQLState(): ?string
    {
        return null;
    }
}
