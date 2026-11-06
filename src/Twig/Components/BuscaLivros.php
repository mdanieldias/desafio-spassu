<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Livro;
use App\Model\Pagina;
use App\Service\LivroService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class BuscaLivros
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'reiniciarPagina')]
    public string $termo = '';

    #[LiveProp(writable: true, url: true)]
    public int $pagina = 1;

    public function __construct(private readonly LivroService $livros)
    {
    }

    public function reiniciarPagina(string $termoAnterior): void
    {
        if ($termoAnterior === $this->termo) {
            return;
        }

        $this->pagina = 1;
    }

    /** @return Pagina<Livro> */
    public function livros(): Pagina
    {
        return $this->livros->listar($this->termo, $this->pagina);
    }
}
