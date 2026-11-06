<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\AutorDto;
use App\Entity\Autor;
use App\Entity\Livro;
use App\Exception\RegistroEmUsoException;
use App\Exception\RegistroNaoEncontradoException;
use App\Repository\AutorRepository;
use App\Service\AutorService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AutorServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AutorService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new AutorService($this->em, $this->createStub(AutorRepository::class));
    }

    public function testCriarPersisteOAutorComONomeDoDto(): void
    {
        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(Autor::class));
        $this->em->expects(self::once())->method('flush');

        $autor = $this->service->criar($this->dto('Machado de Assis'));

        self::assertSame('Machado de Assis', $autor->getNome());
    }

    public function testCriarRemoveEspacosNasPontas(): void
    {
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $autor = $this->service->criar($this->dto('  Machado de Assis  '));

        self::assertSame('Machado de Assis', $autor->getNome());
    }

    public function testCriarFalhaQuandoDtoNaoFoiValidado(): void
    {
        $this->em->expects(self::never())->method('persist');
        $this->expectException(\InvalidArgumentException::class);

        $this->service->criar(new AutorDto());
    }

    public function testAtualizarAlteraONome(): void
    {
        $autor = new Autor('Nome antigo');
        $this->em->expects(self::once())->method('find')->with(Autor::class, 3)->willReturn($autor);
        $this->em->expects(self::once())->method('flush');

        $resultado = $this->service->atualizar(3, $this->dto('Machado de Assis'));

        self::assertSame($autor, $resultado);
        self::assertSame('Machado de Assis', $autor->getNome());
    }

    public function testAtualizarAutorInexistenteLancaNaoEncontrado(): void
    {
        $this->em->expects(self::once())->method('find')->willReturn(null);
        $this->expectException(RegistroNaoEncontradoException::class);

        $this->service->atualizar(999, $this->dto('Machado de Assis'));
    }

    public function testExcluirRemoveOAutor(): void
    {
        $autor = new Autor('Machado de Assis');
        $this->em->expects(self::once())->method('find')->willReturn($autor);
        $this->em->expects(self::once())->method('remove')->with($autor);
        $this->em->expects(self::once())->method('flush');

        $this->service->excluir(1);
    }

    public function testExcluirAutorInexistenteLancaNaoEncontrado(): void
    {
        $this->em->expects(self::once())->method('find')->willReturn(null);
        $this->em->expects(self::never())->method('remove');
        $this->expectException(RegistroNaoEncontradoException::class);

        $this->service->excluir(999);
    }

    public function testExcluirAutorComLivrosLancaEmUso(): void
    {
        $autor = new Autor('Machado de Assis');
        $livros = new ArrayCollection([new Livro('Dom Casmurro', 'Garnier', 1, '1899', '49.90')]);
        (new \ReflectionProperty(Autor::class, 'livros'))->setValue($autor, $livros);

        $this->em->expects(self::once())->method('find')->willReturn($autor);
        $this->em->expects(self::never())->method('remove');

        $this->expectException(RegistroEmUsoException::class);
        $this->expectExceptionMessage('Machado de Assis');

        $this->service->excluir(1);
    }

    public function testExcluirTraduzFkResidualEmExcecaoDeDominio(): void
    {
        $autor = new Autor('Machado de Assis');
        $this->em->expects(self::once())->method('find')->willReturn($autor);
        $this->em->expects(self::once())->method('remove')->with($autor);
        $this->em->expects(self::once())->method('flush')->willThrowException(
            new ForeignKeyConstraintViolationException(new AutorDriverException('fk'), null),
        );

        $this->expectException(RegistroEmUsoException::class);
        $this->expectExceptionMessage('Machado de Assis');

        $this->service->excluir(1);
    }

    private function dto(string $nome): AutorDto
    {
        $dto = new AutorDto();
        $dto->nome = $nome;

        return $dto;
    }
}

final class AutorDriverException extends \Exception implements DriverException
{
    public function getSQLState(): ?string
    {
        return null;
    }
}
