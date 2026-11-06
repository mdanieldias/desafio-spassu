<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class AssuntoControllerTest extends FunctionalTestCase
{
    public function testCadastraRecusaDuplicadoEditaEExclui(): void
    {
        $client = static::createClient();

        $this->enviar($client, '/assuntos/novo', 'Salvar', [
            'assunto[descricao]' => 'Ficcao',
        ]);
        self::assertResponseRedirects('/assuntos');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Assunto cadastrado.');
        $id = $this->idNaListagem($client, 'Ficcao', '#/assuntos/(\d+)/editar#');

        $this->enviar($client, '/assuntos/novo', 'Salvar', [
            'assunto[descricao]' => 'ficcao',
        ]);
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.alert-danger', 'Já existe o assunto "Ficcao"');

        $this->enviar($client, '/assuntos/'.$id.'/editar', 'Salvar', [
            'assunto[descricao]' => 'Haicai',
        ]);
        self::assertResponseRedirects('/assuntos');
        $client->followRedirect();
        $crawler = $this->paginaComTexto($client, 'Haicai');
        $linha = $crawler->filterXPath('//tr[td[contains(normalize-space(.), "Haicai")]]');

        $client->submit($linha->selectButton('Excluir')->form());
        self::assertResponseRedirects('/assuntos');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Assunto excluído.');
        self::assertStringNotContainsString('Haicai', (string) $client->getResponse()->getContent());
    }

    public function testNaoExcluiAssuntoVinculadoALivro(): void
    {
        $client = static::createClient();
        $autorId = $this->criarAutor($client, 'Autor do Assunto');
        $assuntoId = $this->criarAssunto($client, 'Usado');
        $this->criarLivro($client, $autorId, $assuntoId, 'Livro com Assunto');

        $crawler = $client->request('GET', '/assuntos');
        $linha = $crawler->filterXPath('//tr[td[contains(normalize-space(.), "Usado")]]');
        $client->submit($linha->selectButton('Excluir')->form());

        self::assertResponseRedirects('/assuntos');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Não é possível excluir o assunto "Usado"');
        self::assertSelectorTextContains('body', 'Usado');
    }

    private function criarAutor(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $nome): int
    {
        $this->enviar($client, '/autores/novo', 'Salvar', ['autor[nome]' => $nome]);
        $client->followRedirect();

        return $this->idNaListagem($client, $nome, '#/autores/(\d+)/editar#');
    }

    private function criarAssunto(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $descricao): int
    {
        $this->enviar($client, '/assuntos/novo', 'Salvar', ['assunto[descricao]' => $descricao]);
        $client->followRedirect();

        return $this->idNaListagem($client, $descricao, '#/assuntos/(\d+)/editar#');
    }

    private function criarLivro(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, int $autorId, int $assuntoId, string $titulo): void
    {
        $this->enviar($client, '/livros/novo', 'Salvar', [
            'livro[titulo]' => $titulo,
            'livro[editora]' => 'Editora Teste',
            'livro[edicao]' => '1',
            'livro[anoPublicacao]' => '1899',
            'livro[valor]' => '10.00',
            'livro[autores]' => [(string) $autorId],
            'livro[assuntos]' => [(string) $assuntoId],
        ]);
        self::assertResponseRedirects('/livros');
        $client->followRedirect();
    }
}
