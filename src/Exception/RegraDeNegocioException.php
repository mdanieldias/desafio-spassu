<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Base das exceções de domínio. Os controllers capturam esta classe
 * e exibem getMessage() ao usuário (flash message) sem vazar detalhes do banco.
 */
abstract class RegraDeNegocioException extends \RuntimeException
{
}
