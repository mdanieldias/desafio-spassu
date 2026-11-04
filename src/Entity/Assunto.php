<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AssuntoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssuntoRepository::class)]
#[ORM\Table(name: 'assunto')]
class Assunto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codas', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'descricao', type: 'string', length: 20)]
    private string $descricao;

    /** @var Collection<int, Livro> */
    #[ORM\ManyToMany(targetEntity: Livro::class, mappedBy: 'assuntos')]
    private Collection $livros;

    public function __construct(string $descricao)
    {
        $this->descricao = $descricao;
        $this->livros = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): self
    {
        $this->descricao = $descricao;

        return $this;
    }

    /** @return Collection<int, Livro> */
    public function getLivros(): Collection
    {
        return $this->livros;
    }

    public function __toString(): string
    {
        return $this->descricao;
    }
}
