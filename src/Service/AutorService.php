<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\AutorDto;
use App\Entity\Autor;
use App\Exception\RegistroEmUsoException;
use App\Exception\RegistroNaoEncontradoException;
use App\Model\Pagina;
use App\Repository\AutorRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Regras de negócio do cadastro de autores.
 * O DTO já chega validado; aqui só traduzimos a FK de exclusão em exceção de domínio.
 */
final class AutorService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AutorRepository $autores,
    ) {
    }

    public function criar(AutorDto $dto): Autor
    {
        $autor = new Autor($this->nomeDe($dto));

        $this->em->persist($autor);
        $this->em->flush();

        return $autor;
    }

    public function atualizar(int $id, AutorDto $dto): Autor
    {
        $autor = $this->buscar($id);
        $autor->setNome($this->nomeDe($dto));
        $this->em->flush();

        return $autor;
    }

    public function excluir(int $id): void
    {
        $autor = $this->buscar($id);

        if ($autor->getLivros()->count() > 0) {
            throw RegistroEmUsoException::para(sprintf('o autor "%s"', $autor->getNome()));
        }

        $this->em->remove($autor);

        try {
            $this->em->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw RegistroEmUsoException::para(sprintf('o autor "%s"', $autor->getNome()), $e);
        }
    }

    public function buscar(int $id): Autor
    {
        return $this->em->find(Autor::class, $id)
            ?? throw RegistroNaoEncontradoException::autor($id);
    }

    /** @return Pagina<Autor> */
    public function listar(int $pagina = 1): Pagina
    {
        return $this->autores->listar($pagina);
    }

    private function nomeDe(AutorDto $dto): string
    {
        $nome = $this->exigir($dto->nome, 'nome');
        $nome = trim($nome);

        return $nome !== '' ? $nome : throw new \InvalidArgumentException(
            'Campo "nome" ausente: valide o DTO antes de chamar o service.',
        );
    }

    /**
     * @template T
     * @param T|null $valor
     * @return T
     */
    private function exigir(mixed $valor, string $campo): mixed
    {
        return $valor ?? throw new \InvalidArgumentException(
            sprintf('Campo "%s" ausente: valide o DTO antes de chamar o service.', $campo),
        );
    }
}
