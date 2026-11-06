<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class AutorControllerTest extends FunctionalTestCase
{
    public function testCadastraEditaEExcluiAutor(): void
    {
        $client = static::createClient();

        $this->enviar($client, '/autores/novo', 'Salvar', [
            'autor[nome]' => 'Autor Funcional',
        ]);
        self::assertResponseRedirects('/autores');
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Autor cadastrado.');
        $id = $this->idNaListagem($crawler, 'Autor Funcional', '#/autores/(\d+)/editar#');

        $this->enviar($client, '/autores/'.$id.'/editar', 'Salvar', [
            'autor[nome]' => 'Autor Editado',
        ]);
        self::assertResponseRedirects('/autores');
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('body', 'Autor Editado');
        self::assertStringNotContainsString('Autor Funcional', (string) $client->getResponse()->getContent());

        $client->submit($crawler->selectButton('Excluir')->form());
        self::assertResponseRedirects('/autores');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Autor excluído.');
        self::assertStringNotContainsString('Autor Editado', (string) $client->getResponse()->getContent());
    }

    public function testNaoExcluiAutorVinculadoALivro(): void
    {
        $client = static::createClient();
        $autorId = $this->criarAutor($client, 'Autor Vinculado');
        $this->criarLivro($client, $autorId, 'Livro do Autor');

        $crawler = $client->request('GET', '/autores');
        $linha = $crawler->filterXPath('//tr[td[contains(normalize-space(.), "Autor Vinculado")]]');
        $client->submit($linha->selectButton('Excluir')->form());

        self::assertResponseRedirects('/autores');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Não é possível excluir o autor "Autor Vinculado"');
        self::assertSelectorTextContains('body', 'Autor Vinculado');
    }

    public function testExclusaoSemTokenERecusada(): void
    {
        $client = static::createClient();
        $id = $this->criarAutor($client, 'Autor Protegido');

        $client->request('POST', '/autores/'.$id.'/excluir', ['_token' => 'invalido']);

        self::assertResponseStatusCodeSame(403);
    }

    private function criarAutor(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $nome): int
    {
        $this->enviar($client, '/autores/novo', 'Salvar', [
            'autor[nome]' => $nome,
        ]);
        $crawler = $client->followRedirect();

        return $this->idNaListagem($crawler, $nome, '#/autores/(\d+)/editar#');
    }

    private function criarLivro(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, int $autorId, string $titulo): void
    {
        $this->enviar($client, '/livros/novo', 'Salvar', [
            'livro[titulo]' => $titulo,
            'livro[editora]' => 'Editora Teste',
            'livro[edicao]' => '1',
            'livro[anoPublicacao]' => '1899',
            'livro[valor]' => '10.00',
            'livro[autores]' => [(string) $autorId],
            'livro[assuntos]' => [],
        ]);
        self::assertResponseRedirects('/livros');
        $client->followRedirect();
    }
}
