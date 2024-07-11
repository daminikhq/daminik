<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240130091644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX type_idx ON file (type)');
        $this->addSql('CREATE INDEX public_idx ON file (public)');
        $this->addSql('CREATE INDEX created_at_idx ON file (created_at)');
        $this->addSql('CREATE INDEX updated_at_idx ON file (updated_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX type_idx ON file');
        $this->addSql('DROP INDEX public_idx ON file');
        $this->addSql('DROP INDEX created_at_idx ON file');
        $this->addSql('DROP INDEX updated_at_idx ON file');
    }
}
