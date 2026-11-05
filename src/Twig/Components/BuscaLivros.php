<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Livro;
use App\Service\LivroService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class BuscaLivros
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $termo = '';

    public function __construct(private readonly LivroService $livros)
    {
    }

    /** @return list<Livro> */
    public function livros(): array
    {
        return $this->livros->listar($this->termo);
    }
}
