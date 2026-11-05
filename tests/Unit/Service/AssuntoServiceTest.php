<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\AssuntoDto;
use App\Entity\Assunto;
use App\Entity\Livro;
use App\Exception\RegistroDuplicadoException;
use App\Exception\RegistroEmUsoException;
use App\Exception\RegistroNaoEncontradoException;
use App\Repository\AssuntoRepository;
use App\Service\AssuntoService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AssuntoServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AssuntoRepository&MockObject $assuntos;
    private AssuntoService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->assuntos = $this->createMock(AssuntoRepository::class);
        $this->service = new AssuntoService($this->em, $this->assuntos);
    }

    public function testCriarPersisteOAssuntoComADescricaoDoDto(): void
    {
        $this->assuntos->expects(self::once())->method('buscarPorDescricao')->with('Romance')->willReturn(null);
        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(Assunto::class));
        $this->em->expects(self::once())->method('flush');

        $assunto = $this->service->criar($this->dto('Romance'));

        self::assertSame('Romance', $assunto->getDescricao());
    }

    public function testCriarRemoveEspacosNasPontas(): void
    {
        $this->assuntos->expects(self::once())->method('buscarPorDescricao')->with('Romance')->willReturn(null);
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $assunto = $this->service->criar($this->dto('  Romance  '));

        self::assertSame('Romance', $assunto->getDescricao());
    }

    public function testCriarFalhaQuandoDtoNaoFoiValidado(): void
    {
        $this->assuntos->expects(self::never())->method('buscarPorDescricao');
        $this->em->expects(self::never())->method('persist');
        $this->expectException(\InvalidArgumentException::class);

        $this->service->criar(new AssuntoDto());
    }

    public function testCriarRecusaDescricaoJaCadastrada(): void
    {
        $this->assuntos->expects(self::once())->method('buscarPorDescricao')->with('romance')->willReturn($this->assunto(7, 'Romance'));
        $this->em->expects(self::never())->method('persist');

        $this->expectException(RegistroDuplicadoException::class);
        $this->expectExceptionMessage('Já existe o assunto "Romance".');

        $this->service->criar($this->dto('romance'));
    }

    public function testCriarTraduzViolacaoDeUnicidadeEmExcecaoDeDominio(): void
    {
        $this->assuntos->expects(self::once())->method('buscarPorDescricao')->willReturn(null);
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush')->willThrowException(
            new UniqueConstraintViolationException(new AssuntoDriverException('duplicate key'), null),
        );

        $this->expectException(RegistroDuplicadoException::class);
        $this->expectExceptionMessage('Romance');

        $this->service->criar($this->dto('Romance'));
    }

    public function testAtualizarAlteraADescricao(): void
    {
        $assunto = $this->assunto(3, 'Descrição antiga');
        $this->em->expects(self::once())->method('find')->with(Assunto::class, 3)->willReturn($assunto);
        $this->assuntos->expects(self::once())->method('buscarPorDescricao')->with('Romance')->willReturn(null);
        $this->em->expects(self::once())->method('flush');

        $resultado = $this->service->atualizar(3, $this->dto('Romance'));

        self::assertSame($assunto, $resultado);
        self::assertSame('Romance', $assunto->getDescricao());
    }

    public function testAtualizarAssuntoInexistenteLancaNaoEncontrado(): void
    {
        $this->em->expects(self::once())->method('find')->willReturn(null);
        $this->assuntos->expects(self::never())->method('buscarPorDescricao');
        $this->expectException(RegistroNaoEncontradoException::class);

        $this->service->atualizar(999, $this->dto('Romance'));
    }

    public function testAtualizarMantemOProprioAssunto(): void
    {
        $assunto = $this->assunto(3, 'Romance');
        $this->em->expects(self::once())->method('find')->willReturn($assunto);
        $this->assuntos->expects(self::once())->method('buscarPorDescricao')->with('romance')->willReturn($assunto);
        $this->em->expects(self::once())->method('flush');

        $resultado = $this->service->atualizar(3, $this->dto('romance'));

        self::assertSame('romance', $resultado->getDescricao());
    }

    public function testAtualizarRecusaDescricaoDeOutroAssunto(): void
    {
        $assunto = $this->assunto(3, 'Poesia');
        $this->em->expects(self::once())->method('find')->willReturn($assunto);
        $this->assuntos->expects(self::once())->method('buscarPorDescricao')->willReturn($this->assunto(7, 'Romance'));
        $this->em->expects(self::never())->method('flush');

        $this->expectException(RegistroDuplicadoException::class);
        $this->expectExceptionMessage('Já existe o assunto "Romance".');

        $this->service->atualizar(3, $this->dto('romance'));
    }

    public function testExcluirRemoveOAssunto(): void
    {
        $assunto = new Assunto('Romance');
        $this->assuntos->expects(self::never())->method('buscarPorDescricao');
        $this->em->expects(self::once())->method('find')->willReturn($assunto);
        $this->em->expects(self::once())->method('remove')->with($assunto);
        $this->em->expects(self::once())->method('flush');

        $this->service->excluir(1);
    }

    public function testExcluirAssuntoInexistenteLancaNaoEncontrado(): void
    {
        $this->assuntos->expects(self::never())->method('buscarPorDescricao');
        $this->em->expects(self::once())->method('find')->willReturn(null);
        $this->em->expects(self::never())->method('remove');
        $this->expectException(RegistroNaoEncontradoException::class);

        $this->service->excluir(999);
    }

    public function testExcluirAssuntoComLivrosLancaEmUso(): void
    {
        $assunto = new Assunto('Romance');
        $this->assuntos->expects(self::never())->method('buscarPorDescricao');
        $livros = new ArrayCollection([new Livro('Dom Casmurro', 'Garnier', 1, '1899', '49.90')]);
        (new \ReflectionProperty(Assunto::class, 'livros'))->setValue($assunto, $livros);

        $this->em->expects(self::once())->method('find')->willReturn($assunto);
        $this->em->expects(self::never())->method('remove');

        $this->expectException(RegistroEmUsoException::class);
        $this->expectExceptionMessage('Romance');

        $this->service->excluir(1);
    }

    public function testExcluirTraduzFkResidualEmExcecaoDeDominio(): void
    {
        $assunto = new Assunto('Romance');
        $this->assuntos->expects(self::never())->method('buscarPorDescricao');
        $this->em->expects(self::once())->method('find')->willReturn($assunto);
        $this->em->expects(self::once())->method('remove')->with($assunto);
        $this->em->expects(self::once())->method('flush')->willThrowException(
            new ForeignKeyConstraintViolationException(new AssuntoDriverException('fk'), null),
        );

        $this->expectException(RegistroEmUsoException::class);
        $this->expectExceptionMessage('Romance');

        $this->service->excluir(1);
    }

    private function dto(string $descricao): AssuntoDto
    {
        $dto = new AssuntoDto();
        $dto->descricao = $descricao;

        return $dto;
    }

    private function assunto(int $id, string $descricao): Assunto
    {
        $assunto = new Assunto($descricao);
        (new \ReflectionProperty(Assunto::class, 'id'))->setValue($assunto, $id);

        return $assunto;
    }
}

final class AssuntoDriverException extends \Exception implements DriverException
{
    public function getSQLState(): ?string
    {
        return null;
    }
}
