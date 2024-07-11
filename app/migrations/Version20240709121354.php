<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240709121354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_system ADD bucket VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_715A3734E73F36A6 ON file_system (bucket)');
        $this->addSql('CREATE INDEX bucket_idx ON file_system (bucket)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_715A3734E73F36A6 ON file_system');
        $this->addSql('DROP INDEX bucket_idx ON file_system');
        $this->addSql('ALTER TABLE file_system DROP bucket');
    }
}
