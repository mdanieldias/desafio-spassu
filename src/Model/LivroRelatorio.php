<?php

declare(strict_types=1);

namespace App\Model;

final readonly class LivroRelatorio
{
    public function __construct(
        public string $titulo,
        public string $editora,
        public int $edicao,
        public string $anoPublicacao,
        public string $valor,
        public string $assuntos,
    ) {
    }
}
