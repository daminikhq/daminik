<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240312093406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE revision ADD file_system_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE revision ADD CONSTRAINT FK_6D6315CC5E9A90D3 FOREIGN KEY (file_system_id) REFERENCES file_system (id)');
        $this->addSql('CREATE INDEX IDX_6D6315CC5E9A90D3 ON revision (file_system_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE revision DROP FOREIGN KEY FK_6D6315CC5E9A90D3');
        $this->addSql('DROP INDEX IDX_6D6315CC5E9A90D3 ON revision');
        $this->addSql('ALTER TABLE revision DROP file_system_id');
    }
}
