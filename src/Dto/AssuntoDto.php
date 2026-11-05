<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Assunto;
use Symfony\Component\Validator\Constraints as Assert;

/** Entrada validada do formulário de assunto. A entidade não conhece regra de tela. */
final class AssuntoDto
{
    #[Assert\NotBlank(message: 'Informe a descrição.', normalizer: 'trim')]
    #[Assert\Length(max: 20, maxMessage: 'A descrição deve ter no máximo {{ limit }} caracteres.')]
    public ?string $descricao = null;

    public static function deAssunto(Assunto $assunto): self
    {
        $dto = new self();
        $dto->descricao = $assunto->getDescricao();

        return $dto;
    }
}
