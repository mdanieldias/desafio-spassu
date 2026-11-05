<?php

declare(strict_types=1);

namespace App\Exception;

final class RegistroDuplicadoException extends RegraDeNegocioException
{
    public static function livro(string $titulo, string $editora, int $edicao, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Já existe o livro "%s" da editora %s (edição %d).', $titulo, $editora, $edicao),
            0,
            $previous,
        );
    }

    public static function assunto(string $descricao, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Já existe o assunto "%s".', $descricao),
            0,
            $previous,
        );
    }
}
