<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240314092208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file ADD public_filename_slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX public_filename_slug_idx ON file (workspace_id, public_filename_slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX public_filename_slug_idx ON file');
        $this->addSql('ALTER TABLE file DROP public_filename_slug');
    }
}
