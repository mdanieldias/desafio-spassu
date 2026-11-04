<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\LivroDto;
use App\Entity\Livro;
use App\Exception\RegistroDuplicadoException;
use App\Exception\RegistroNaoEncontradoException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Regras de negócio do cadastro de livros.
 * O DTO já chega validado (Symfony Validator); aqui só traduzimos erros de banco
 * em exceções de domínio específicas, sem try/catch genérico.
 */
final class LivroService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function criar(LivroDto $dto): Livro
    {
        $livro = new Livro(
            $this->exigir($dto->titulo, 'titulo'),
            $this->exigir($dto->editora, 'editora'),
            $this->exigir($dto->edicao, 'edicao'),
            $this->exigir($dto->anoPublicacao, 'anoPublicacao'),
            $this->exigir($dto->valor, 'valor'),
        );
        $livro->definirAutores($dto->autores)->definirAssuntos($dto->assuntos);

        $this->em->persist($livro);
        $this->gravar($livro);

        return $livro;
    }

    public function atualizar(int $id, LivroDto $dto): Livro
    {
        $livro = $this->buscar($id);

        $livro->atualizar(
            $this->exigir($dto->titulo, 'titulo'),
            $this->exigir($dto->editora, 'editora'),
            $this->exigir($dto->edicao, 'edicao'),
            $this->exigir($dto->anoPublicacao, 'anoPublicacao'),
            $this->exigir($dto->valor, 'valor'),
        );
        $livro->definirAutores($dto->autores)->definirAssuntos($dto->assuntos);

        $this->gravar($livro);

        return $livro;
    }

    public function excluir(int $id): void
    {
        $livro = $this->buscar($id);

        // As linhas de livro_autor/livro_assunto são removidas por ON DELETE CASCADE.
        $this->em->remove($livro);
        $this->em->flush();
    }

    public function buscar(int $id): Livro
    {
        return $this->em->find(Livro::class, $id)
            ?? throw RegistroNaoEncontradoException::livro($id);
    }

    private function gravar(Livro $livro): void
    {
        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw RegistroDuplicadoException::livro(
                $livro->getTitulo(),
                $livro->getEditora(),
                $livro->getEdicao(),
                $e,
            );
        }
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
