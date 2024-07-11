<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230721134444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE revision_file_storage_url (id INT AUTO_INCREMENT NOT NULL, revision_id INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, url VARCHAR(511) NOT NULL, timeout DATETIME NOT NULL, public_url VARCHAR(511) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_624305591DFA7C8F (revision_id), UNIQUE INDEX revision_width_height_idx (revision_id, width, height), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE revision_file_storage_url ADD CONSTRAINT FK_624305591DFA7C8F FOREIGN KEY (revision_id) REFERENCES revision (id)');
        $this->addSql('ALTER TABLE user RENAME INDEX username_idx TO UNIQ_8D93D649F85E0677');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE revision_file_storage_url DROP FOREIGN KEY FK_624305591DFA7C8F');
        $this->addSql('DROP TABLE revision_file_storage_url');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649f85e0677 TO username_idx');
    }
}
