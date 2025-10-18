<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017191206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add workspace, membership, and workspace invite tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workspace_invites (id VARCHAR(36) NOT NULL, workspace_id VARCHAR(36) NOT NULL, invited_by_id VARCHAR(36) NOT NULL, invited_user_id VARCHAR(36) DEFAULT NULL, responded_by_id VARCHAR(36) DEFAULT NULL, email VARCHAR(191) NOT NULL, token VARCHAR(120) NOT NULL, status VARCHAR(32) NOT NULL, role VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, responded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DB43816782D40A1F ON workspace_invites (workspace_id)');
        $this->addSql('CREATE INDEX IDX_DB438167A7B4A7E3 ON workspace_invites (invited_by_id)');
        $this->addSql('CREATE INDEX IDX_DB438167C58DAD6E ON workspace_invites (invited_user_id)');
        $this->addSql('CREATE INDEX IDX_DB438167296135A7 ON workspace_invites (responded_by_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_workspace_invite_token ON workspace_invites (token)');
        $this->addSql('COMMENT ON COLUMN workspace_invites.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN workspace_invites.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN workspace_invites.responded_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE workspace_memberships (id VARCHAR(36) NOT NULL, workspace_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, role VARCHAR(32) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_160CED6E82D40A1F ON workspace_memberships (workspace_id)');
        $this->addSql('CREATE INDEX IDX_160CED6EA76ED395 ON workspace_memberships (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_workspace_membership ON workspace_memberships (workspace_id, user_id)');
        $this->addSql('COMMENT ON COLUMN workspace_memberships.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE workspaces (id VARCHAR(36) NOT NULL, created_by_id VARCHAR(36) NOT NULL, name VARCHAR(120) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7FE8F3CBB03A8386 ON workspaces (created_by_id)');
        $this->addSql('COMMENT ON COLUMN workspaces.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN workspaces.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE workspace_invites ADD CONSTRAINT FK_DB43816782D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workspace_invites ADD CONSTRAINT FK_DB438167A7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workspace_invites ADD CONSTRAINT FK_DB438167C58DAD6E FOREIGN KEY (invited_user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workspace_invites ADD CONSTRAINT FK_DB438167296135A7 FOREIGN KEY (responded_by_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workspace_memberships ADD CONSTRAINT FK_160CED6E82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workspace_memberships ADD CONSTRAINT FK_160CED6EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workspaces ADD CONSTRAINT FK_7FE8F3CBB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE workspace_invites DROP CONSTRAINT FK_DB43816782D40A1F');
        $this->addSql('ALTER TABLE workspace_invites DROP CONSTRAINT FK_DB438167A7B4A7E3');
        $this->addSql('ALTER TABLE workspace_invites DROP CONSTRAINT FK_DB438167C58DAD6E');
        $this->addSql('ALTER TABLE workspace_invites DROP CONSTRAINT FK_DB438167296135A7');
        $this->addSql('ALTER TABLE workspace_memberships DROP CONSTRAINT FK_160CED6E82D40A1F');
        $this->addSql('ALTER TABLE workspace_memberships DROP CONSTRAINT FK_160CED6EA76ED395');
        $this->addSql('ALTER TABLE workspaces DROP CONSTRAINT FK_7FE8F3CBB03A8386');
        $this->addSql('DROP TABLE workspace_invites');
        $this->addSql('DROP TABLE workspace_memberships');
        $this->addSql('DROP TABLE workspaces');
    }
}
