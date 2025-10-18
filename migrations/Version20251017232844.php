<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017232844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE assistants ADD gender VARCHAR(16) DEFAULT 'female' NOT NULL");
        $this->addSql("UPDATE assistants SET gender = 'female' WHERE gender IS NULL");
        $this->addSql('ALTER TABLE assistants ALTER COLUMN gender DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assistants DROP gender');
    }
}
