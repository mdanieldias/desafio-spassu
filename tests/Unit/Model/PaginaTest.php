<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\Pagina;
use PHPUnit\Framework\TestCase;

final class PaginaTest extends TestCase
{
    public function testTotalDePaginasArredondaParaCima(): void
    {
        self::assertSame(1, (new Pagina([], 1, 0))->totalDePaginas());
        self::assertSame(1, (new Pagina([], 1, 10))->totalDePaginas());
        self::assertSame(2, (new Pagina([], 1, 11))->totalDePaginas());
    }
}
