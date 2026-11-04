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
}
