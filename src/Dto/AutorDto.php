<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Autor;
use Symfony\Component\Validator\Constraints as Assert;

/** Entrada validada do formulário de autor. A entidade não conhece regra de tela. */
final class AutorDto
{
    #[Assert\NotBlank(message: 'Informe o nome.', normalizer: 'trim')]
    #[Assert\Length(max: 40, maxMessage: 'O nome deve ter no máximo {{ limit }} caracteres.')]
    public ?string $nome = null;

    public static function deAutor(Autor $autor): self
    {
        $dto = new self();
        $dto->nome = $autor->getNome();

        return $dto;
    }
}
