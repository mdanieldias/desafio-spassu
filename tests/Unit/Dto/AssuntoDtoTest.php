<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\AssuntoDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AssuntoDtoTest extends TestCase
{
    public function testDtoValidoNaoGeraViolacoes(): void
    {
        self::assertCount(0, $this->validar($this->dtoValido()));
    }

    /** @param callable(AssuntoDto): void $mutacao */
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

    /** @return iterable<string, array{string, callable(AssuntoDto): void}> */
    public static function casosInvalidos(): iterable
    {
        yield 'descrição vazia' => ['descricao', static fn (AssuntoDto $d) => $d->descricao = ''];
        yield 'descrição só com espaços' => ['descricao', static fn (AssuntoDto $d) => $d->descricao = '   '];
        yield 'descrição com mais de 20' => ['descricao', static fn (AssuntoDto $d) => $d->descricao = str_repeat('a', 21)];
    }

    private function dtoValido(): AssuntoDto
    {
        $dto = new AssuntoDto();
        $dto->descricao = 'Romance';

        return $dto;
    }

    private function validar(AssuntoDto $dto): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);
    }
}
