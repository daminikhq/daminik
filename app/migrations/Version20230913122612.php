<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230913122612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE asset_collection (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, creator_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, public TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3AAC866882D40A1F (workspace_id), INDEX IDX_3AAC866861220EA6 (creator_id), UNIQUE INDEX slug_idx (workspace_id, slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE asset_collection ADD CONSTRAINT FK_3AAC866882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE asset_collection ADD CONSTRAINT FK_3AAC866861220EA6 FOREIGN KEY (creator_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asset_collection DROP FOREIGN KEY FK_3AAC866882D40A1F');
        $this->addSql('ALTER TABLE asset_collection DROP FOREIGN KEY FK_3AAC866861220EA6');
        $this->addSql('DROP TABLE asset_collection');
    }
}
