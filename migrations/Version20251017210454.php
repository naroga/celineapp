<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017210454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assistants ALTER COLUMN profile_picture TYPE TEXT');
        $this->addSql('ALTER TABLE assistants ALTER COLUMN email DROP NOT NULL');
        $this->addSql('ALTER TABLE assistants ALTER COLUMN phone_number DROP NOT NULL');
        $this->addSql('ALTER TABLE assistants ALTER COLUMN playbook DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE assistants ALTER COLUMN profile_picture TYPE VARCHAR(255) USING LEFT(profile_picture, 255)");
        $this->addSql('ALTER TABLE assistants ALTER COLUMN email SET NOT NULL');
        $this->addSql('ALTER TABLE assistants ALTER COLUMN phone_number SET NOT NULL');
        $this->addSql('ALTER TABLE assistants ALTER COLUMN playbook SET NOT NULL');
    }
}
