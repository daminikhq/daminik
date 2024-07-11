<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230428140006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE revision (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, uploader_id INT NOT NULL, filepath VARCHAR(511) NOT NULL, mime VARCHAR(255) DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6D6315CC93CB796C (file_id), INDEX IDX_6D6315CC16678C77 (uploader_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE revision ADD CONSTRAINT FK_6D6315CC93CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
        $this->addSql('ALTER TABLE revision ADD CONSTRAINT FK_6D6315CC16678C77 FOREIGN KEY (uploader_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE file ADD active_revision_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610490589C4 FOREIGN KEY (active_revision_id) REFERENCES revision (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C9F3610490589C4 ON file (active_revision_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F3610490589C4');
        $this->addSql('ALTER TABLE revision DROP FOREIGN KEY FK_6D6315CC93CB796C');
        $this->addSql('ALTER TABLE revision DROP FOREIGN KEY FK_6D6315CC16678C77');
        $this->addSql('DROP TABLE revision');
        $this->addSql('DROP INDEX UNIQ_8C9F3610490589C4 ON file');
        $this->addSql('ALTER TABLE file DROP active_revision_id');
    }
}
