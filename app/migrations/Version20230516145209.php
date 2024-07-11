<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230516145209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file_tag (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, tag_id INT NOT NULL, creator_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2CCA391A93CB796C (file_id), INDEX IDX_2CCA391ABAD26311 (tag_id), INDEX IDX_2CCA391A61220EA6 (creator_id), UNIQUE INDEX file_tag_idx (file_id, tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, workspace_id INT NOT NULL, creator_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_389B78382D40A1F (workspace_id), INDEX IDX_389B78361220EA6 (creator_id), UNIQUE INDEX slug_idx (workspace_id, slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE file_tag ADD CONSTRAINT FK_2CCA391A93CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
        $this->addSql('ALTER TABLE file_tag ADD CONSTRAINT FK_2CCA391ABAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
        $this->addSql('ALTER TABLE file_tag ADD CONSTRAINT FK_2CCA391A61220EA6 FOREIGN KEY (creator_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B78382D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B78361220EA6 FOREIGN KEY (creator_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_tag DROP FOREIGN KEY FK_2CCA391A93CB796C');
        $this->addSql('ALTER TABLE file_tag DROP FOREIGN KEY FK_2CCA391ABAD26311');
        $this->addSql('ALTER TABLE file_tag DROP FOREIGN KEY FK_2CCA391A61220EA6');
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B78382D40A1F');
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B78361220EA6');
        $this->addSql('DROP TABLE file_tag');
        $this->addSql('DROP TABLE tag');
    }
}
