<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230606123927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspace ADD icon_file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD CONSTRAINT FK_8D94001931F4227C FOREIGN KEY (icon_file_id) REFERENCES file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D94001931F4227C ON workspace (icon_file_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspace DROP FOREIGN KEY FK_8D94001931F4227C');
        $this->addSql('DROP INDEX UNIQ_8D94001931F4227C ON workspace');
        $this->addSql('ALTER TABLE workspace DROP icon_file_id');
    }
}
