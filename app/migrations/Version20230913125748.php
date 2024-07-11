<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230913125748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file_asset_collection (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, asset_collection_id INT NOT NULL, added_by_id INT NOT NULL, added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D15E940093CB796C (file_id), INDEX IDX_D15E9400923C8F9C (asset_collection_id), INDEX IDX_D15E940055B127A4 (added_by_id), UNIQUE INDEX file_asset_collection_idx (file_id, asset_collection_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE file_asset_collection ADD CONSTRAINT FK_D15E940093CB796C FOREIGN KEY (file_id) REFERENCES file (id)');
        $this->addSql('ALTER TABLE file_asset_collection ADD CONSTRAINT FK_D15E9400923C8F9C FOREIGN KEY (asset_collection_id) REFERENCES asset_collection (id)');
        $this->addSql('ALTER TABLE file_asset_collection ADD CONSTRAINT FK_D15E940055B127A4 FOREIGN KEY (added_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_asset_collection DROP FOREIGN KEY FK_D15E940093CB796C');
        $this->addSql('ALTER TABLE file_asset_collection DROP FOREIGN KEY FK_D15E9400923C8F9C');
        $this->addSql('ALTER TABLE file_asset_collection DROP FOREIGN KEY FK_D15E940055B127A4');
        $this->addSql('DROP TABLE file_asset_collection');
    }
}
