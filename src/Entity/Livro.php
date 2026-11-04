<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LivroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivroRepository::class)]
#[ORM\Table(name: 'livro')]
#[ORM\UniqueConstraint(name: 'uq_livro_titulo_editora_edicao', columns: ['titulo', 'editora', 'edicao'])]
class Livro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'codl', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'titulo', type: 'string', length: 40)]
    private string $titulo;

    #[ORM\Column(name: 'editora', type: 'string', length: 40)]
    private string $editora;

    #[ORM\Column(name: 'edicao', type: 'integer')]
    private int $edicao;

    #[ORM\Column(name: 'ano_publicacao', type: 'string', length: 4)]
    private string $anoPublicacao;

    /** Valor em R$, guardado como string para não perder precisão (DECIMAL). */
    #[ORM\Column(name: 'valor', type: 'decimal', precision: 10, scale: 2)]
    private string $valor;

    /** @var Collection<int, Autor> */
    #[ORM\ManyToMany(targetEntity: Autor::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'livro_autor')]
    #[ORM\JoinColumn(name: 'livro_codl', referencedColumnName: 'codl', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'autor_codau', referencedColumnName: 'codau', onDelete: 'RESTRICT')]
    private Collection $autores;

    /** @var Collection<int, Assunto> */
    #[ORM\ManyToMany(targetEntity: Assunto::class, inversedBy: 'livros')]
    #[ORM\JoinTable(name: 'livro_assunto')]
    #[ORM\JoinColumn(name: 'livro_codl', referencedColumnName: 'codl', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'assunto_codas', referencedColumnName: 'codas', onDelete: 'RESTRICT')]
    private Collection $assuntos;

    public function __construct(
        string $titulo,
        string $editora,
        int $edicao,
        string $anoPublicacao,
        string $valor,
    ) {
        $this->titulo = $titulo;
        $this->editora = $editora;
        $this->edicao = $edicao;
        $this->anoPublicacao = $anoPublicacao;
        $this->valor = $valor;
        $this->autores = new ArrayCollection();
        $this->assuntos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function getEditora(): string
    {
        return $this->editora;
    }

    public function getEdicao(): int
    {
        return $this->edicao;
    }

    public function getAnoPublicacao(): string
    {
        return $this->anoPublicacao;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    /** Atualiza os dados escalares de uma só vez, mantendo o objeto sempre consistente. */
    public function atualizar(
        string $titulo,
        string $editora,
        int $edicao,
        string $anoPublicacao,
        string $valor,
    ): self {
        $this->titulo = $titulo;
        $this->editora = $editora;
        $this->edicao = $edicao;
        $this->anoPublicacao = $anoPublicacao;
        $this->valor = $valor;

        return $this;
    }

    /** @return Collection<int, Autor> */
    public function getAutores(): Collection
    {
        return $this->autores;
    }

    /** @param iterable<Autor> $autores */
    public function definirAutores(iterable $autores): self
    {
        $this->autores->clear();
        foreach ($autores as $autor) {
            if (!$this->autores->contains($autor)) {
                $this->autores->add($autor);
            }
        }

        return $this;
    }

    /** @return Collection<int, Assunto> */
    public function getAssuntos(): Collection
    {
        return $this->assuntos;
    }

    /** @param iterable<Assunto> $assuntos */
    public function definirAssuntos(iterable $assuntos): self
    {
        $this->assuntos->clear();
        foreach ($assuntos as $assunto) {
            if (!$this->assuntos->contains($assunto)) {
                $this->assuntos->add($assunto);
            }
        }

        return $this;
    }
}
