<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Repository\AssuntoRepository;
use App\Repository\AutorRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SelecaoRegistros
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $tipo = 'autor';

    /** Nome do campo múltiplo já com [], por exemplo livro[autores][]. */
    #[LiveProp]
    public string $campo = '';

    #[LiveProp]
    public string $rotulo = '';

    #[LiveProp]
    public bool $obrigatorio = false;

    /** @var list<int> */
    #[LiveProp]
    public array $selecionados = [];

    #[LiveProp(writable: true)]
    public string $proximoId = '';

    public function __construct(
        private readonly AutorRepository $autores,
        private readonly AssuntoRepository $assuntos,
    ) {
    }

    #[LiveAction]
    public function adicionar(): void
    {
        if ($this->proximoId === '') {
            return;
        }

        $id = (int) $this->proximoId;
        $ids = $this->ids();

        foreach ($this->registros() as $registro) {
            if ($this->idDe($registro) !== $id || in_array($id, $ids, true)) {
                continue;
            }

            $ids[] = $id;
            $this->selecionados = $ids;
            $this->proximoId = '';

            return;
        }
    }

    #[LiveAction]
    public function remover(#[LiveArg] string $id): void
    {
        $id = (int) $id;
        $this->selecionados = array_values(array_filter(
            $this->ids(),
            static fn (int $atual): bool => $atual !== $id,
        ));
    }

    /** @return list<Autor|Assunto> */
    public function escolhidos(): array
    {
        $porId = [];
        foreach ($this->registros() as $registro) {
            $porId[$this->idDe($registro)] = $registro;
        }

        $escolhidos = [];
        foreach ($this->ids() as $id) {
            if (isset($porId[$id])) {
                $escolhidos[] = $porId[$id];
            }
        }

        return $escolhidos;
    }

    /** @return list<Autor|Assunto> */
    public function disponiveis(): array
    {
        $ids = $this->ids();

        return array_values(array_filter(
            $this->registros(),
            fn (Autor|Assunto $registro): bool => !in_array($this->idDe($registro), $ids, true),
        ));
    }

    /** @return list<int> */
    private function ids(): array
    {
        return array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $this->selecionados)));
    }

    /** @return list<Autor|Assunto> */
    private function registros(): array
    {
        return match ($this->tipo) {
            'autor' => $this->autores->findBy([], ['nome' => 'ASC']),
            'assunto' => $this->assuntos->findBy([], ['descricao' => 'ASC']),
            default => throw new \InvalidArgumentException('Tipo de seleção desconhecido.'),
        };
    }

    private function idDe(Autor|Assunto $registro): int
    {
        return (int) $registro->getId();
    }
}
