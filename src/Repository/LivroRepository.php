<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Livro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Livro> */
class LivroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livro::class);
    }

    public function buscarDuplicado(string $titulo, string $editora, int $edicao): ?Livro
    {
        $livro = $this->findOneBy([
            'titulo' => $titulo,
            'editora' => $editora,
            'edicao' => $edicao,
        ]);

        return $livro instanceof Livro ? $livro : null;
    }

    /** @return list<Livro> */
    public function buscarPorTermo(?string $termo): array
    {
        $qb = $this->createQueryBuilder('l')->orderBy('l.titulo', 'ASC');

        if ($termo !== null && trim($termo) !== '') {
            $qb->andWhere('LOWER(l.titulo) LIKE :termo OR LOWER(l.editora) LIKE :termo')
                ->setParameter('termo', '%' . mb_strtolower(trim($termo)) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Linhas da view vw_livros_por_autor, já ordenadas por autor e título.
     * Autor sem livro volta com livro_id nulo.
     *
     * @return list<array<string, mixed>>
     */
    public function listarParaRelatorio(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(<<<'SQL'
            SELECT
                autor_id,
                autor_nome,
                livro_id,
                titulo,
                editora,
                edicao,
                ano_publicacao,
                valor,
                assuntos
            FROM vw_livros_por_autor
            ORDER BY autor_nome, titulo NULLS LAST
            SQL);
    }
}
