<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240205151512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_entry ADD user_data JSON DEFAULT NULL');
        $this->addSql('CREATE INDEX user_id_idx ON log_entry (user_id)');
        $this->addSql('CREATE INDEX entity_class_idx ON log_entry (entity_class)');
        $this->addSql('CREATE INDEX entity_id_idx ON log_entry (entity_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX user_id_idx ON log_entry');
        $this->addSql('DROP INDEX entity_class_idx ON log_entry');
        $this->addSql('DROP INDEX entity_id_idx ON log_entry');
        $this->addSql('ALTER TABLE log_entry DROP user_data');
    }
}
