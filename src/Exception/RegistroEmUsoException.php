<?php

declare(strict_types=1);

namespace App\Exception;

/** Lançada quando a exclusão violaria uma FK (ex.: autor que ainda possui livros). */
final class RegistroEmUsoException extends RegraDeNegocioException
{
    public static function para(string $descricao, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Não é possível excluir %s: existem registros vinculados.', $descricao),
            0,
            $previous,
        );
    }
}
