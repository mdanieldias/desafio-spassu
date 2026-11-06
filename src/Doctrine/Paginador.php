<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Model\Pagina;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;

/**
 * Aplica limite e deslocamento numa consulta e conta o total em separado.
 */
final class Paginador
{
    private readonly OffsetPaginator $paginas;

    public function __construct()
    {
        // As listagens não fazem fetch de coleção; o limite simples basta.
        $this->paginas = new OffsetPaginator(fetchJoinCollection: false);
    }

    public function paginar(Query $query, int $pagina, int $porPagina = Pagina::POR_PAGINA): Pagina
    {
        $porPagina = max(1, $porPagina);
        $pagina = max(1, $pagina);

        $resultado = $this->paginas->paginate($query, Window::fromPageNumberAndSize($pagina, $porPagina));

        if ($pagina > $resultado->getPageCount()) {
            $resultado = $this->paginas->paginate($query, $resultado->getLastWindow());
        }

        return new Pagina(
            $resultado->getItems(),
            $resultado->getPageNumber(),
            $resultado->getTotalCount(),
            $porPagina,
        );
    }
}
