<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230405094547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file CHANGE filename filepath VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX filename_idx ON file (workspace_id, public_filename)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX filename_idx ON file');
        $this->addSql('ALTER TABLE file CHANGE filepath filename VARCHAR(255) NOT NULL');
    }
}
