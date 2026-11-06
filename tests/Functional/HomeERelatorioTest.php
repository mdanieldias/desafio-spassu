<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class HomeERelatorioTest extends FunctionalTestCase
{
    public function testHomeRedirecionaParaLivros(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/livros');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Livros');
    }

    public function testRelatorioGeraPdf(): void
    {
        $client = static::createClient();
        $client->request('GET', '/relatorio');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF', (string) $client->getResponse()->getContent());
    }
}
