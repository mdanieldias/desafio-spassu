<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Massa de demonstração: livros com um ou vários autores, um livro sem assunto,
 * um autor sem livro e um assunto sem livro.
 */
class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $autores = $this->persistirAutores($manager);
        $assuntos = $this->persistirAssuntos($manager);

        foreach ($this->livros($autores, $assuntos) as $livro) {
            $manager->persist($livro);
        }

        $manager->flush();
    }

    /** @return array<string, Autor> */
    private function persistirAutores(ObjectManager $manager): array
    {
        $nomes = [
            'Machado de Assis',
            'Clarice Lispector',
            'Guimarães Rosa',
            'Graciliano Ramos',
            'Jorge Amado',
            'Érico Veríssimo',
            'Rachel de Queiroz',
            'Lima Barreto',
            'Carlos Drummond',
            'Cecília Meireles',
            'Monteiro Lobato',
            'José de Alencar',
            'Álvares de Azevedo',
            'Lygia F. Telles',
            'João Cabral',
            'Autor sem livros',
        ];

        $autores = [];
        foreach ($nomes as $nome) {
            $autor = new Autor($nome);
            $manager->persist($autor);
            $autores[$nome] = $autor;
        }

        return $autores;
    }

    /** @return array<string, Assunto> */
    private function persistirAssuntos(ObjectManager $manager): array
    {
        $descricoes = ['Romance', 'Conto', 'Poesia', 'Infantil', 'Regionalismo', 'Crônica', 'Fantasia'];

        $assuntos = [];
        foreach ($descricoes as $descricao) {
            $assunto = new Assunto($descricao);
            $manager->persist($assunto);
            $assuntos[$descricao] = $assunto;
        }

        return $assuntos;
    }

    /**
     * @param array<string, Autor>   $autores
     * @param array<string, Assunto> $assuntos
     *
     * @return list<Livro>
     */
    private function livros(array $autores, array $assuntos): array
    {
        $antologia = [
            'Machado de Assis',
            'Clarice Lispector',
            'Guimarães Rosa',
            'Graciliano Ramos',
            'Jorge Amado',
            'Érico Veríssimo',
            'Rachel de Queiroz',
            'Lima Barreto',
            'Carlos Drummond',
            'Cecília Meireles',
            'Monteiro Lobato',
            'José de Alencar',
            'Álvares de Azevedo',
            'Lygia F. Telles',
            'João Cabral',
        ];

        $definicoes = [
            ['Dom Casmurro', 'Garnier', 1, '1899', '49.90', ['Machado de Assis'], ['Romance']],
            ['Dom Casmurro', 'Garnier', 2, '1900', '39.90', ['Machado de Assis', 'Lima Barreto'], ['Romance', 'Conto']],
            ['Memórias Póstumas', 'Garnier', 1, '1881', '45.00', ['Machado de Assis'], ['Romance']],
            ['A Hora da Estrela', 'Rocco', 1, '1977', '42.00', ['Clarice Lispector'], ['Conto']],
            ['Grande Sertão', 'José Olympio', 1, '1956', '68.50', ['Guimarães Rosa'], ['Romance', 'Regionalismo']],
            ['Vidas Secas', 'José Olympio', 1, '1938', '36.00', ['Graciliano Ramos'], ['Romance']],
            ['Capitães da Areia', 'Martins', 1, '1937', '44.90', ['Jorge Amado'], ['Romance']],
            ['O Tempo e o Vento', 'Globo', 1, '1949', '55.00', ['Érico Veríssimo'], ['Romance']],
            ['O Quinze', 'José Olympio', 1, '1930', '32.50', ['Rachel de Queiroz'], ['Regionalismo']],
            ['Triste Fim', 'Revista do Brasil', 1, '1915', '28.00', ['Lima Barreto'], ['Romance']],
            ['Reinações de Narizinho', 'Nacional', 1, '1931', '29.90', ['Monteiro Lobato'], ['Infantil']],
            ['Iracema', 'Garnier', 1, '1865', '27.00', ['José de Alencar'], ['Romance']],
            ['Antologia Poética', 'Companhia', 1, '2020', '89.90', $antologia, ['Poesia', 'Crônica']],
            ['Caderno sem tema', 'Oficina', 1, '2010', '19.90', ['Carlos Drummond'], []],
        ];

        $livros = [];
        foreach ($definicoes as [$titulo, $editora, $edicao, $ano, $valor, $nomesAutores, $nomesAssuntos]) {
            $livro = new Livro($titulo, $editora, $edicao, $ano, $valor);
            $livro->definirAutores(array_map(static fn (string $nome): Autor => $autores[$nome], $nomesAutores));
            $livro->definirAssuntos(array_map(static fn (string $nome): Assunto => $assuntos[$nome], $nomesAssuntos));
            $livros[] = $livro;
        }

        return $livros;
    }
}
