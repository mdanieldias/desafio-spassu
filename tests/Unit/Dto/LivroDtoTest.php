<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\LivroDto;
use App\Entity\Autor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class LivroDtoTest extends TestCase
{
    public function testDtoValidoNaoGeraViolacoes(): void
    {
        self::assertCount(0, $this->validar($this->dtoValido()));
    }

    public function testAnoAtualEAceito(): void
    {
        $dto = $this->dtoValido();
        $dto->anoPublicacao = date('Y');

        self::assertCount(0, $this->validar($dto));
    }

    /** @param callable(LivroDto): void $mutacao */
    #[DataProvider('casosInvalidos')]
    public function testCamposInvalidosGeramViolacao(string $campo, callable $mutacao): void
    {
        $dto = $this->dtoValido();
        $mutacao($dto);

        $campos = [];
        foreach ($this->validar($dto) as $violacao) {
            $campos[] = $violacao->getPropertyPath();
        }

        self::assertContains($campo, $campos);
    }

    /** @return iterable<string, array{string, callable(LivroDto): void}> */
    public static function casosInvalidos(): iterable
    {
        yield 'título vazio' => ['titulo', static fn (LivroDto $d) => $d->titulo = ''];
        yield 'título com mais de 40' => ['titulo', static fn (LivroDto $d) => $d->titulo = str_repeat('a', 41)];
        yield 'editora vazia' => ['editora', static fn (LivroDto $d) => $d->editora = ''];
        yield 'edição zero' => ['edicao', static fn (LivroDto $d) => $d->edicao = 0];
        yield 'ano com 3 dígitos' => ['anoPublicacao', static fn (LivroDto $d) => $d->anoPublicacao = '202'];
        yield 'ano com letras' => ['anoPublicacao', static fn (LivroDto $d) => $d->anoPublicacao = 'abcd'];
        yield 'ano maior que o atual' => ['anoPublicacao', static fn (LivroDto $d) => $d->anoPublicacao = (string) ((int) date('Y') + 1)];
        yield 'valor negativo' => ['valor', static fn (LivroDto $d) => $d->valor = '-1.00'];
        yield 'valor com 3 casas' => ['valor', static fn (LivroDto $d) => $d->valor = '10.999'];
        yield 'sem autores' => ['autores', static fn (LivroDto $d) => $d->autores = []];
    }

    private function dtoValido(): LivroDto
    {
        $dto = new LivroDto();
        $dto->titulo = 'Dom Casmurro';
        $dto->editora = 'Garnier';
        $dto->edicao = 1;
        $dto->anoPublicacao = '1899';
        $dto->valor = '49.90';
        $dto->autores = [new Autor('Machado de Assis')];

        return $dto;
    }

    private function validar(LivroDto $dto): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);
    }
}
