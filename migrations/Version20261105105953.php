<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A comparação sem diferenciar maiúsculas fica no AssuntoRepository.
 * Este índice cobre a descrição idêntica, inclusive duas gravações ao mesmo tempo.
 */
final class Version20261105105953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Impede assunto com a mesma descrição';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uq_assunto_descricao ON assunto (descricao)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uq_assunto_descricao');
    }
}
