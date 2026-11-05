<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Objeto de transferência entre formulário/controller e service.
 * Concentra as validações de entrada; a entidade fica livre de regras de formulário.
 */
final class LivroDto
{
    #[Assert\NotBlank(message: 'Informe o título.')]
    #[Assert\Length(max: 40, maxMessage: 'O título deve ter no máximo {{ limit }} caracteres.')]
    public ?string $titulo = null;

    #[Assert\NotBlank(message: 'Informe a editora.')]
    #[Assert\Length(max: 40, maxMessage: 'A editora deve ter no máximo {{ limit }} caracteres.')]
    public ?string $editora = null;

    #[Assert\NotNull(message: 'Informe a edição.')]
    #[Assert\Positive(message: 'A edição deve ser um número positivo.')]
    public ?int $edicao = null;

    #[Assert\NotBlank(message: 'Informe o ano de publicação.')]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'O ano deve ter 4 dígitos (ex.: 2024).')]
    #[Assert\Range(min: 1000, max: 9999, notInRangeMessage: 'Ano de publicação inválido.')]
    public ?string $anoPublicacao = null;

    /** Valor em R$ como string decimal ("49.90"), compatível com DECIMAL(10,2). */
    #[Assert\NotBlank(message: 'Informe o valor.')]
    #[Assert\Regex(pattern: '/^\d{1,8}(\.\d{1,2})?$/', message: 'Valor inválido. Use até 8 dígitos e 2 casas decimais.')]
    public ?string $valor = null;

    /** @var list<Autor> */
    #[Assert\Count(min: 1, minMessage: 'Selecione ao menos um autor.')]
    public array $autores = [];

    /** @var list<Assunto> */
    public array $assuntos = [];

    #[Assert\Callback]
    public function validarAnoPublicacao(ExecutionContextInterface $context): void
    {
        if (!is_string($this->anoPublicacao) || !preg_match('/^\d{4}$/', $this->anoPublicacao)) {
            return;
        }

        $anoAtual = (int) date('Y');
        if ((int) $this->anoPublicacao > $anoAtual) {
            $context->buildViolation('O ano de publicação não pode ser maior que {{ limit }}.')
                ->setParameter('{{ limit }}', (string) $anoAtual)
                ->atPath('anoPublicacao')
                ->addViolation();
        }
    }

    public static function deLivro(Livro $livro): self
    {
        $dto = new self();
        $dto->titulo = $livro->getTitulo();
        $dto->editora = $livro->getEditora();
        $dto->edicao = $livro->getEdicao();
        $dto->anoPublicacao = $livro->getAnoPublicacao();
        $dto->valor = $livro->getValor();
        $dto->autores = $livro->getAutores()->toArray();
        $dto->assuntos = $livro->getAssuntos()->toArray();

        return $dto;
    }
}
