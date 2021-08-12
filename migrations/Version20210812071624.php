<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210812071624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity ADD created_at VARCHAR(255) NOT NULL, CHANGE status status INT NOT NULL');
        $this->addSql('ALTER TABLE license_plate ADD created_at VARCHAR(255) NOT NULL, ADD updated_at VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity DROP created_at, CHANGE status status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE license_plate DROP created_at, DROP updated_at');
    }
}
