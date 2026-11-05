<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Assunto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Assunto> */
class AssuntoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assunto::class);
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
