# Cadastro de Livros

Desafio técnico: CRUD de **Livro**, **Autor** e **Assunto** com relatório agrupado por autor, a partir de uma view do banco.

## Stack

| Camada         | Escolha                                          | Motivo                                                                  |
| -------------- | ------------------------------------------------ | ----------------------------------------------------------------------- |
| Backend        | Symfony 8.1 / PHP 8.5                            | Versão estável atual do framework, com DI, validação e forms            |
| Persistência   | Doctrine ORM + Migrations                        | Schema versionado; a view do relatório entra por migration (SQL nativo) |
| Banco          | PostgreSQL 16                                    | Views, `string_agg`, CHECK constraints                                  |
| Front          | Twig + Bootstrap 5 + TypeScript (Webpack Encore) | CSS exigido pelo desafio; TS só onde há interação                       |
| Interatividade | Symfony UX Live Component                        | **Somente** onde houver ganho real                                      |
| Relatório      | Dompdf (PDF) sobre a view `vw_livros_por_autor`  | Equivalente PHP ao Crystal/ReportViewer                                 |
| Testes         | PHPUnit 13                                       | TDD: unitários (service, DTO) e funcionais (controllers)                |
| Infra          | Docker Compose (php-fpm, nginx, postgres, node)  | Sobe em etapas: PHP e banco, Composer, permissão do cache, depois o Node |

## Como subir

```bash
docker compose up -d --build db php nginx
docker compose exec php composer install
docker compose exec php sh -c 'mkdir -p var/cache var/log && chmod -R 777 var'
docker compose up -d node
docker compose exec php bin/console doctrine:migrations:migrate -n
# app: http://localhost:8080
```

O Node sobe por último porque o `package.json` aponta para `vendor/symfony/ux-live-component`, que só existe depois do `composer install`. O `chmod` é necessário porque o Composer cria `var/cache` como root e o PHP-FPM não consegue gravar nele. Sem rota cadastrada, essa URL responde 404 do Symfony.

## Dados de demonstração

A massa fica em `src/DataFixtures/AppFixtures.php`. Um arquivo só, porque autores, assuntos e livros formam um conjunto: o livro precisa do autor e do assunto já gravados no mesmo `load()`.

```bash
docker compose exec php bin/console doctrine:fixtures:load -n
```

Esse comando apaga os registros atuais do banco e carrega a fixture de novo. A suíte de testes não usa essa massa: cada teste funcional cria e desfaz os próprios dados.

## Como testar

```bash
docker compose exec php php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate -n --env=test
docker compose exec php composer test
```

`composer test:unit` roda só os unitários, sem banco. `composer test` inclui a suíte funcional, que usa o banco `livros_test` e desfaz cada teste.

## Modelo de dados

Segue o modelo do desafio, com estes ajustes:

- `livro.valor NUMERIC(10,2)`: campo de valor (R$) pedido no enunciado. No PHP trafega como `string` para não perder precisão.
- `UNIQUE (titulo, editora, edicao)` em `livro`: evita duplicidade.
- `CHECK` em edição (> 0), ano (4 dígitos) e valor (>= 0): espelham o DTO (defesa em profundidade).
- Índices nas colunas de FK do lado inverso das tabelas de ligação (equivalem aos `FKIndex2` do modelo).
- Nomes em `snake_case` minúsculo (`codl`, `codau`, `codas`), padrão PostgreSQL.

### Regras de exclusão

| Ação | Comportamento |
|---|---|
| Excluir **livro** | Remove também os vínculos em `livro_autor` e `livro_assunto` (`ON DELETE CASCADE`) |
| Excluir **autor** com livros | Bloqueado (`RESTRICT`): mensagem amigável via `RegistroEmUsoException` |
| Excluir **assunto** com livros | Bloqueado (`RESTRICT`): idem |

### View do relatório

`vw_livros_por_autor` devolve uma linha por (autor, livro) com os assuntos agregados em texto.
Um livro com dois autores aparece sob cada um deles. Autores sem livros também aparecem (com campos do livro nulos).

## Arquitetura

```
src/
  Controller/   finos: recebem request, chamam service, devolvem resposta
  Dto/          entrada validada (Symfony Validator)
  Entity/       mapeamento Doctrine
  Form/         Symfony Forms (um FormType por CRUD)
  Repository/   consultas
  Service/      regras de negócio e tradução de erros de banco
  Exception/    exceções de domínio (mensagens amigáveis)
  Twig/Components/  Live Components pontuais
```

### Tratamento de erros

Sem `catch (\Exception)` genérico. O service captura apenas exceções específicas do DBAL e as converte:

- `UniqueConstraintViolationException` → `RegistroDuplicadoException`
- `ForeignKeyConstraintViolationException` → `RegistroEmUsoException`
- registro ausente → `RegistroNaoEncontradoException`

Os controllers capturam `RegraDeNegocioException` e exibem a mensagem em *flash message*.

### Symfony Forms x Live Component

Padrão: **Symfony Forms** com DTO e validação. **Live Component** apenas onde a interatividade se justifica:

1. Busca/filtro da listagem de livros, com atualização em tempo real.
2. Seleção de autores e assuntos com adição/remoção dinâmica na tela do livro.

Os demais formulários (Autor, Assunto) ficam como forms tradicionais.
