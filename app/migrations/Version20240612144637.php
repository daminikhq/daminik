<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240612144637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ADD uploaded_mb INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD uploaded_mb INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD uploaded_mb INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership DROP uploaded_mb');
        $this->addSql('ALTER TABLE `user` DROP uploaded_mb');
        $this->addSql('ALTER TABLE workspace DROP uploaded_mb');
    }
}
