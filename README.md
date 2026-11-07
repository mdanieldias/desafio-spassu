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

## Implementação local

Na máquina bastam o Docker e o Docker Compose em execução. PHP 8.5, Composer, Node e PostgreSQL ficam nos containers. Os comandos abaixo rodam na raiz do repositório.

### 1. Subir a aplicação

```bash
docker compose up -d --build db php nginx
docker compose exec php composer install
docker compose exec php sh -c 'mkdir -p var/cache var/log && chmod -R 777 var'
docker compose up -d node
docker compose exec php bin/console doctrine:migrations:migrate -n
```

O código é montado em `/var/www/html` dentro do container `php`. O `composer install` e o `bin/console` rodam lá, não no PHP do host. O Node sobe por último porque o `package.json` aponta para `vendor/symfony/ux-live-component`, que só existe depois do Composer. O `chmod` é necessário porque o Composer cria `var/cache` como root e o PHP-FPM não consegue gravar nele.

O container `node` executa `npm install` e `npm run watch`, e publica o CSS e o JavaScript em `public/build`. Sem esse passo a página abre sem o estilo do Bootstrap.

Abra http://localhost:8080. A raiz redireciona para `/livros`. Autores ficam em `/autores`, assuntos em `/assuntos` e o PDF em `/relatorio`.

| Serviço | No host | Na rede do Compose |
| --- | --- | --- |
| Aplicação (nginx) | http://localhost:8080 | `nginx:80` |
| PostgreSQL 16 | `localhost:5433` | `db:5432` |

A porta 5433 no host evita conflito com um PostgreSQL local na 5432. Usuário, senha e banco: `app` / `app` / `livros`. O `DATABASE_URL` do serviço `php` no `docker-compose.yml` é o que a aplicação usa; ele aponta para `db:5432`.

O segredo de desenvolvimento está em `.env.dev`. O `.env` deixa `APP_SECRET` vazio no bloco do Flex, e o Symfony carrega o valor de `.env.dev` em seguida. Nada disso precisa ser editado para rodar localmente.

Os dados do Postgres ficam no volume `db_data`. `docker compose down` para os containers e mantém o banco. `docker compose down -v` apaga o volume: depois é preciso rodar as migrations de novo.

Se a 8080 ou a 5433 já estiver ocupada, troque só o lado do host em `docker-compose.yml` (`"8080:80"` e `"5433:5432"`).

### 2. Dados de demonstração

A massa fica em `src/DataFixtures/AppFixtures.php`. Um arquivo só, porque autores, assuntos e livros formam um conjunto: o livro precisa do autor e do assunto já gravados no mesmo `load()`.

```bash
docker compose exec php bin/console doctrine:fixtures:load -n
```

Esse comando apaga os registros atuais do banco e carrega a fixture de novo. A listagem mostra 10 registros por página. A suíte de testes não usa essa massa: cada teste funcional cria e desfaz os próprios dados.

### 3. Testes

O banco de teste é outro: `livros_test`, no mesmo PostgreSQL. Crie-o uma vez e rode a suíte dentro do container:

```bash
docker compose exec php php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate -n --env=test
docker compose exec php composer test
```

`composer test:unit` roda só os unitários, sem banco. `composer test` inclui a suíte funcional, que desfaz cada teste.

### Dia a dia

Qualquer comando Symfony segue o mesmo caminho, porque o PHP está no container:

```bash
docker compose exec php bin/console <comando>
docker compose exec php composer <comando>
```

O `npm run watch` do container `node` recompila o front quando um arquivo em `assets/` muda. Para ver o log: `docker compose logs -f node`.

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
