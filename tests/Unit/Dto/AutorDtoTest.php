<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\AutorDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AutorDtoTest extends TestCase
{
    public function testDtoValidoNaoGeraViolacoes(): void
    {
        self::assertCount(0, $this->validar($this->dtoValido()));
    }

    /** @param callable(AutorDto): void $mutacao */
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

    /** @return iterable<string, array{string, callable(AutorDto): void}> */
    public static function casosInvalidos(): iterable
    {
        yield 'nome vazio' => ['nome', static fn (AutorDto $d) => $d->nome = ''];
        yield 'nome só com espaços' => ['nome', static fn (AutorDto $d) => $d->nome = '   '];
        yield 'nome com mais de 40' => ['nome', static fn (AutorDto $d) => $d->nome = str_repeat('a', 41)];
    }

    private function dtoValido(): AutorDto
    {
        $dto = new AutorDto();
        $dto->nome = 'Machado de Assis';

        return $dto;
    }

    private function validar(AutorDto $dto): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);
    }
}
