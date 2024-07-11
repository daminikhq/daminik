<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230626144841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file_user_meta_data (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, user_id INT NOT NULL, favorite TINYINT(1) DEFAULT NULL, INDEX IDX_A36AB49F93CB796C (file_id), INDEX IDX_A36AB49FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE file_user_meta_data ADD CONSTRAINT FK_A36AB49F93CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
        $this->addSql('ALTER TABLE file_user_meta_data ADD CONSTRAINT FK_A36AB49FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_user_meta_data DROP FOREIGN KEY FK_A36AB49F93CB796C');
        $this->addSql('ALTER TABLE file_user_meta_data DROP FOREIGN KEY FK_A36AB49FA76ED395');
        $this->addSql('DROP TABLE file_user_meta_data');
    }
}
