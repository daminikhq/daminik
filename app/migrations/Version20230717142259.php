<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230717142259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file_system (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, config JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workspace ADD filesystem_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD CONSTRAINT FK_8D9400194F05E558 FOREIGN KEY (filesystem_id) REFERENCES file_system (id)');
        $this->addSql('CREATE INDEX IDX_8D9400194F05E558 ON workspace (filesystem_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspace DROP FOREIGN KEY FK_8D9400194F05E558');
        $this->addSql('DROP TABLE file_system');
        $this->addSql('DROP INDEX IDX_8D9400194F05E558 ON workspace');
        $this->addSql('ALTER TABLE workspace DROP filesystem_id');
    }
}
