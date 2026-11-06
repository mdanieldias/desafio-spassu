<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class LivroControllerTest extends FunctionalTestCase
{
    use InteractsWithLiveComponents;

    public function testCadastraListaRecusaDuplicadoEAnoFuturo(): void
    {
        $client = static::createClient();
        $autorId = $this->criarAutor($client, 'Machado Teste');

        $this->enviar($client, '/livros/novo', 'Salvar', $this->dados($autorId, 'Dom Funcional', '1899'));
        self::assertResponseRedirects('/livros');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Livro cadastrado.');
        self::assertSelectorTextContains('body', 'Dom Funcional');
        self::assertSelectorTextContains('body', 'R$ 12,50');

        $this->enviar($client, '/livros/novo', 'Salvar', $this->dados($autorId, 'Dom Funcional', '1899'));
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.alert-danger', 'Já existe o livro "Dom Funcional"');

        $this->enviar($client, '/livros/novo', 'Salvar', $this->dados($autorId, 'Ano Futuro', (string) ((int) date('Y') + 1)));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'não pode ser maior que ' . date('Y'));
    }

    public function testEditaEExcluiLivro(): void
    {
        $client = static::createClient();
        $autorId = $this->criarAutor($client, 'Editor de Livro');
        $this->enviar($client, '/livros/novo', 'Salvar', $this->dados($autorId, 'Titulo Original', '1900'));
        $crawler = $client->followRedirect();
        $id = $this->idNaListagem($crawler, 'Titulo Original', '#/livros/(\d+)/editar#');

        $this->enviar($client, '/livros/'.$id.'/editar', 'Salvar', $this->dados($autorId, 'Titulo Novo', '1900'));
        self::assertResponseRedirects('/livros');
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('body', 'Titulo Novo');
        self::assertStringNotContainsString('Titulo Original', (string) $client->getResponse()->getContent());

        $linha = $crawler->filterXPath('//tr[td[contains(normalize-space(.), "Titulo Novo")]]');
        $client->submit($linha->selectButton('Excluir')->form());
        self::assertResponseRedirects('/livros');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Livro excluído.');
        self::assertStringNotContainsString('Titulo Novo', (string) $client->getResponse()->getContent());
    }

    public function testBuscaFiltraPeloTitulo(): void
    {
        $client = static::createClient();
        $autorId = $this->criarAutor($client, 'Autor da Busca');
        $this->enviar($client, '/livros/novo', 'Salvar', $this->dados($autorId, 'Encontravel', '1910'));
        $client->followRedirect();
        $this->enviar($client, '/livros/novo', 'Salvar', $this->dados($autorId, 'Oculto', '1911', '2'));
        $client->followRedirect();

        $html = (string) $this->createLiveComponent('BuscaLivros', client: $client)
            ->set('termo', 'Encontr')
            ->render();

        self::assertStringContainsString('Encontravel', $html);
        self::assertStringNotContainsString('Oculto', $html);
    }

    /** @return array<string, mixed> */
    private function dados(int $autorId, string $titulo, string $ano, string $edicao = '1'): array
    {
        return [
            'livro[titulo]' => $titulo,
            'livro[editora]' => 'Garnier',
            'livro[edicao]' => $edicao,
            'livro[anoPublicacao]' => $ano,
            'livro[valor]' => '12.50',
            'livro[autores]' => [(string) $autorId],
            'livro[assuntos]' => [],
        ];
    }

    private function criarAutor(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $nome): int
    {
        $this->enviar($client, '/autores/novo', 'Salvar', ['autor[nome]' => $nome]);
        $crawler = $client->followRedirect();

        return $this->idNaListagem($crawler, $nome, '#/autores/(\d+)/editar#');
    }
}
