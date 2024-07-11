<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240308102856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_system ADD year VARCHAR(5) DEFAULT NULL, ADD uuid VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX type_idx ON file_system (type)');
        $this->addSql('CREATE UNIQUE INDEX year_uuid_idx ON file_system (year, uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX type_idx ON file_system');
        $this->addSql('DROP INDEX year_uuid_idx ON file_system');
        $this->addSql('ALTER TABLE file_system DROP year, DROP uuid');
    }
}
