<?php

declare(strict_types=1);

namespace App\Repository;

use App\Doctrine\Paginador;
use App\Entity\Assunto;
use App\Model\Pagina;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Assunto> */
class AssuntoRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Paginador $paginador,
    ) {
        parent::__construct($registry, Assunto::class);
    }

    /** @return Pagina<Assunto> */
    public function listar(int $pagina = 1): Pagina
    {
        $query = $this->createQueryBuilder('a')
            ->orderBy('a.descricao', 'ASC')
            ->getQuery();

        return $this->paginador->paginar($query, $pagina);
    }

    /** Compara sem diferenciar maiúsculas, para não cadastrar o mesmo assunto duas vezes. */
    public function buscarPorDescricao(string $descricao): ?Assunto
    {
        return $this->createQueryBuilder('a')
            ->where('LOWER(a.descricao) = LOWER(:descricao)')
            ->setParameter('descricao', $descricao)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
