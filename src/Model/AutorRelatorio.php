<?php

declare(strict_types=1);

namespace App\Model;

final readonly class AutorRelatorio
{
    /** @param list<LivroRelatorio> $livros */
    public function __construct(
        public string $nome,
        public array $livros,
    ) {
    }
}
