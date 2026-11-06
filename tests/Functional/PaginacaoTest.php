<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class PaginacaoTest extends FunctionalTestCase
{
    use InteractsWithLiveComponents;

    public function testAutoresDividemEmPaginasDeDez(): void
    {
        $client = static::createClient();
        $this->criarSequencia($client, '/autores/novo', 'Salvar', 'autor[nome]', '000 Autor %02d');

        $primeira = $client->request('GET', '/autores');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('tbody', '000 Autor 01');
        self::assertStringNotContainsString('000 Autor 11', (string) $client->getResponse()->getContent());
        self::assertSame('1', trim($primeira->filter('nav[aria-label="Paginação"] [aria-current="page"]')->text()));
        self::assertGreaterThan(0, $primeira->filter('a[rel="next"]')->count());

        $segunda = $client->request('GET', '/autores?pagina=2');
        self::assertSelectorTextContains('tbody', '000 Autor 11');
        self::assertStringNotContainsString('000 Autor 01', (string) $client->getResponse()->getContent());
        self::assertSame('2', trim($segunda->filter('nav[aria-label="Paginação"] [aria-current="page"]')->text()));

        $alem = $client->request('GET', '/autores?pagina=99');
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $alem->filter('tbody tr')->count());
        self::assertNotSame('99', trim($alem->filter('nav[aria-label="Paginação"] [aria-current="page"]')->text()));
    }

    public function testAssuntosDividemEmPaginasDeDez(): void
    {
        $client = static::createClient();
        $this->criarSequencia($client, '/assuntos/novo', 'Salvar', 'assunto[descricao]', '000 Assunto %02d');

        $client->request('GET', '/assuntos');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('tbody', '000 Assunto 01');
        self::assertStringNotContainsString('000 Assunto 11', (string) $client->getResponse()->getContent());

        $client->request('GET', '/assuntos?pagina=2');
        self::assertSelectorTextContains('tbody', '000 Assunto 11');
        self::assertStringNotContainsString('000 Assunto 01', (string) $client->getResponse()->getContent());
    }

    public function testBuscaDeLivrosVoltaParaAPrimeiraPagina(): void
    {
        $client = static::createClient();
        $this->enviar($client, '/autores/novo', 'Salvar', ['autor[nome]' => '000 Autor Busca']);
        $client->followRedirect();
        $autorId = $this->idNaListagem($client, '000 Autor Busca', '#/autores/(\d+)/editar#');

        for ($i = 1; $i <= 11; ++$i) {
            $this->enviar($client, '/livros/novo', 'Salvar', [
                'livro[titulo]' => sprintf('000 Livro %02d', $i),
                'livro[editora]' => 'Paginacao',
                'livro[edicao]' => '1',
                'livro[anoPublicacao]' => '1899',
                'livro[valor]' => '10.00',
                'livro[autores]' => [(string) $autorId],
                'livro[assuntos]' => [],
            ]);
            self::assertResponseRedirects('/livros');
        }

        $pagina2 = (string) $this->createLiveComponent('BuscaLivros', client: $client)
            ->set('pagina', 2)
            ->render();
        self::assertStringContainsString('000 Livro 11', $pagina2);
        self::assertStringNotContainsString('000 Livro 01', $pagina2);

        $filtrada = (string) $this->createLiveComponent('BuscaLivros', client: $client)
            ->set('pagina', 2)
            ->set('termo', '000 Livro')
            ->render();
        self::assertStringContainsString('000 Livro 01', $filtrada);
        self::assertStringNotContainsString('000 Livro 11', $filtrada);
    }

    private function criarSequencia(KernelBrowser $client, string $url, string $botao, string $campo, string $formato): void
    {
        for ($i = 1; $i <= 11; ++$i) {
            $this->enviar($client, $url, $botao, [$campo => sprintf($formato, $i)]);
            self::assertResponseRedirects();
        }
    }
}
