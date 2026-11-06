<?php

declare(strict_types=1);

namespace App\Repository;

use App\Doctrine\Paginador;
use App\Entity\Livro;
use App\Model\Pagina;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Livro> */
class LivroRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Paginador $paginador,
    ) {
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

    /** @return Pagina<Livro> */
    public function buscarPorTermo(?string $termo, int $pagina = 1): Pagina
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.titulo', 'ASC')
            ->addOrderBy('l.edicao', 'ASC');

        if ($termo !== null && trim($termo) !== '') {
            $qb->andWhere('LOWER(l.titulo) LIKE :termo OR LOWER(l.editora) LIKE :termo')
                ->setParameter('termo', '%' . mb_strtolower(trim($termo)) . '%');
        }

        return $this->paginador->paginar($qb->getQuery(), $pagina);
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
