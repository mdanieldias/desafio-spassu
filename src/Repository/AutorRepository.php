<?php

declare(strict_types=1);

namespace App\Repository;

use App\Doctrine\Paginador;
use App\Entity\Autor;
use App\Model\Pagina;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Autor> */
class AutorRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Paginador $paginador,
    ) {
        parent::__construct($registry, Autor::class);
    }

    /** @return Pagina<Autor> */
    public function listar(int $pagina = 1): Pagina
    {
        $query = $this->createQueryBuilder('a')
            ->orderBy('a.nome', 'ASC')
            ->getQuery();

        return $this->paginador->paginar($query, $pagina);
    }
}
