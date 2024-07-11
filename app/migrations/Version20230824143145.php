<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230824143145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file ADD ai_tags JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE file_tag CHANGE creator_id creator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tag CHANGE creator_id creator_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file DROP ai_tags');
        $this->addSql('ALTER TABLE file_tag CHANGE creator_id creator_id INT NOT NULL');
        $this->addSql('ALTER TABLE tag CHANGE creator_id creator_id INT NOT NULL');
    }
}
