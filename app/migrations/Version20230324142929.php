<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230324142929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invitation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, workspace_id INT NOT NULL, invitee_email VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, valid_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F11D61A2A76ED395 (user_id), INDEX IDX_F11D61A282D40A1F (workspace_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invitation_user (invitation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_40921A1DA35D7AF0 (invitation_id), INDEX IDX_40921A1DA76ED395 (user_id), PRIMARY KEY(invitation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A282D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id)');
        $this->addSql('ALTER TABLE invitation_user ADD CONSTRAINT FK_40921A1DA35D7AF0 FOREIGN KEY (invitation_id) REFERENCES invitation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation_user ADD CONSTRAINT FK_40921A1DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2A76ED395');
        $this->addSql('ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A282D40A1F');
        $this->addSql('ALTER TABLE invitation_user DROP FOREIGN KEY FK_40921A1DA35D7AF0');
        $this->addSql('ALTER TABLE invitation_user DROP FOREIGN KEY FK_40921A1DA76ED395');
        $this->addSql('DROP TABLE invitation');
        $this->addSql('DROP TABLE invitation_user');
    }
}
