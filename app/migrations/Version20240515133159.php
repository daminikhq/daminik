<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240515133159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_access_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, workspace_id INT NOT NULL, token VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BCC804C5A76ED395 (user_id), INDEX IDX_BCC804C582D40A1F (workspace_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE api_access_token ADD CONSTRAINT FK_BCC804C5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE api_access_token ADD CONSTRAINT FK_BCC804C582D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE user ADD bot TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_access_token DROP FOREIGN KEY FK_BCC804C5A76ED395');
        $this->addSql('ALTER TABLE api_access_token DROP FOREIGN KEY FK_BCC804C582D40A1F');
        $this->addSql('DROP TABLE api_access_token');
        $this->addSql('ALTER TABLE `user` DROP bot');
    }
}
