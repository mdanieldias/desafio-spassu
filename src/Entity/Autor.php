<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AutorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutorRepository::class)]
#[ORM\Table(name: 'autor')]
class Autor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codau', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nome', type: 'string', length: 40)]
    private string $nome;

    /** @var Collection<int, Livro> */
    #[ORM\ManyToMany(targetEntity: Livro::class, mappedBy: 'autores')]
    private Collection $livros;

    public function __construct(string $nome)
    {
        $this->nome = $nome;
        $this->livros = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = $nome;

        return $this;
    }

    /** @return Collection<int, Livro> */
    public function getLivros(): Collection
    {
        return $this->livros;
    }

    public function __toString(): string
    {
        return $this->nome;
    }
}
