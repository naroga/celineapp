<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017194515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create assistants table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assistants (id VARCHAR(36) NOT NULL, workspace_id VARCHAR(36) NOT NULL, name VARCHAR(120) NOT NULL, profile_picture VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, phone_number VARCHAR(30) NOT NULL, playbook TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EA18B43582D40A1F ON assistants (workspace_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_assistants_workspace_email ON assistants (workspace_id, email)');
        $this->addSql('COMMENT ON COLUMN assistants.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN assistants.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE assistants ADD CONSTRAINT FK_EA18B43582D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE assistants DROP CONSTRAINT FK_EA18B43582D40A1F');
        $this->addSql('DROP TABLE assistants');
    }
}
