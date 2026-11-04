<?php

declare(strict_types=1);

namespace App\Exception;

final class RegistroNaoEncontradoException extends RegraDeNegocioException
{
    public static function livro(int $id): self
    {
        return new self(sprintf('Livro #%d não foi encontrado.', $id));
    }

    public static function autor(int $id): self
    {
        return new self(sprintf('Autor #%d não foi encontrado.', $id));
    }
}
