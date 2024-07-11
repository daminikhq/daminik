<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240506135419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE registration_code (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, code VARCHAR(255) NOT NULL, admin_notice LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', valid_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_B82B274477153098 (code), INDEX IDX_B82B2744B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE registration_code ADD CONSTRAINT FK_B82B2744B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD registration_code_id INT DEFAULT NULL, ADD initial_invitation_id INT DEFAULT NULL, ADD source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64967ABABB1 FOREIGN KEY (registration_code_id) REFERENCES registration_code (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64986F1B844 FOREIGN KEY (initial_invitation_id) REFERENCES invitation (id)');
        $this->addSql('CREATE INDEX IDX_8D93D64967ABABB1 ON user (registration_code_id)');
        $this->addSql('CREATE INDEX IDX_8D93D64986F1B844 ON user (initial_invitation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64967ABABB1');
        $this->addSql('ALTER TABLE registration_code DROP FOREIGN KEY FK_B82B2744B03A8386');
        $this->addSql('DROP TABLE registration_code');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64986F1B844');
        $this->addSql('DROP INDEX IDX_8D93D64967ABABB1 ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D64986F1B844 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP registration_code_id, DROP initial_invitation_id, DROP source');
    }
}
