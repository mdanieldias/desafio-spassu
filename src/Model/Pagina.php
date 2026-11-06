<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Um recorte da listagem: os itens da página pedida e o total para montar os links.
 *
 * @template T of object
 */
final readonly class Pagina
{
    public const POR_PAGINA = 10;

    /**
     * @param list<T> $itens
     */
    public function __construct(
        public array $itens,
        public int $numero,
        public int $total,
        public int $porPagina = self::POR_PAGINA,
    ) {
    }

    public function totalDePaginas(): int
    {
        if ($this->total <= 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->porPagina);
    }
}
