<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\AssuntoDto;
use App\Entity\Assunto;
use App\Exception\RegistroDuplicadoException;
use App\Exception\RegistroEmUsoException;
use App\Exception\RegistroNaoEncontradoException;
use App\Model\Pagina;
use App\Repository\AssuntoRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Regras de negócio do cadastro de assuntos.
 * O DTO já chega validado. Aqui recusamos descrição repetida (sem diferenciar
 * maiúsculas) e traduzimos a violação de unicidade ou de FK em exceção de domínio.
 */
final class AssuntoService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AssuntoRepository $assuntos,
    ) {
    }

    public function criar(AssuntoDto $dto): Assunto
    {
        $descricao = $this->descricaoDe($dto);
        $this->garantirUnico($descricao);

        $assunto = new Assunto($descricao);
        $this->em->persist($assunto);
        $this->gravar($assunto);

        return $assunto;
    }

    public function atualizar(int $id, AssuntoDto $dto): Assunto
    {
        $assunto = $this->buscar($id);
        $descricao = $this->descricaoDe($dto);
        $this->garantirUnico($descricao, $assunto->getId());
        $assunto->setDescricao($descricao);
        $this->gravar($assunto);

        return $assunto;
    }

    public function excluir(int $id): void
    {
        $assunto = $this->buscar($id);

        if ($assunto->getLivros()->count() > 0) {
            throw RegistroEmUsoException::para(sprintf('o assunto "%s"', $assunto->getDescricao()));
        }

        $this->em->remove($assunto);

        try {
            $this->em->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw RegistroEmUsoException::para(sprintf('o assunto "%s"', $assunto->getDescricao()), $e);
        }
    }

    public function buscar(int $id): Assunto
    {
        return $this->em->find(Assunto::class, $id)
            ?? throw RegistroNaoEncontradoException::assunto($id);
    }

    /** @return Pagina<Assunto> */
    public function listar(int $pagina = 1): Pagina
    {
        return $this->assuntos->listar($pagina);
    }

    private function garantirUnico(string $descricao, ?int $ignorarId = null): void
    {
        $existente = $this->assuntos->buscarPorDescricao($descricao);

        if ($existente !== null && $existente->getId() !== $ignorarId) {
            throw RegistroDuplicadoException::assunto($existente->getDescricao());
        }
    }

    private function gravar(Assunto $assunto): void
    {
        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw RegistroDuplicadoException::assunto($assunto->getDescricao(), $e);
        }
    }

    private function descricaoDe(AssuntoDto $dto): string
    {
        $descricao = $this->exigir($dto->descricao, 'descricao');
        $descricao = trim($descricao);

        return $descricao !== '' ? $descricao : throw new \InvalidArgumentException(
            'Campo "descricao" ausente: valide o DTO antes de chamar o service.',
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
