<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class FunctionalTestCase extends WebTestCase
{
    protected function enviar(KernelBrowser $client, string $url, string $botao, array $campos): Crawler
    {
        $crawler = $client->request('GET', $url);
        $form = $crawler->selectButton($botao)->form();
        $valores = $form->getPhpValues();

        foreach ($campos as $caminho => $valor) {
            $this->definir($valores, $caminho, $valor);
        }

        $client->request($form->getMethod(), $form->getUri(), $valores);

        return $client->getCrawler();
    }

    protected function idNaListagem(Crawler $crawler, string $texto, string $padrao): int
    {
        $linha = $crawler->filterXPath(sprintf('//tr[td[contains(normalize-space(.), "%s")]]', $texto));
        self::assertGreaterThan(0, $linha->count(), sprintf('Não achei "%s" na listagem.', $texto));

        $href = (string) $linha->filter('a')->first()->attr('href');
        self::assertSame(1, preg_match($padrao, $href, $encontrado), $href);

        return (int) $encontrado[1];
    }

    /** @param array<string, mixed> $valores */
    private function definir(array &$valores, string $caminho, mixed $valor): void
    {
        $partes = explode('[', str_replace(']', '', $caminho));
        $cursor = &$valores;
        foreach ($partes as $parte) {
            if (!isset($cursor[$parte]) || !is_array($cursor[$parte])) {
                $cursor[$parte] = [];
            }
            $cursor = &$cursor[$parte];
        }
        $cursor = $valor;
    }
}
