<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017201011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add assistant provider defaults and conversation history tables';
    }

    public function up(Schema $schema): void
    {
        // add provider defaults to assistants
        $this->addSql('ALTER TABLE assistants ADD default_provider VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE assistants ADD default_model VARCHAR(160) DEFAULT NULL');

        // create conversations table
        $this->addSql('CREATE TABLE conversations (id VARCHAR(36) NOT NULL, assistant_id VARCHAR(36) NOT NULL, workspace_id VARCHAR(36) NOT NULL, title VARCHAR(160) DEFAULT NULL, provider_name VARCHAR(80) DEFAULT NULL, model VARCHAR(160) DEFAULT NULL, metadata JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C2521BF1E05387EF ON conversations (assistant_id)');
        $this->addSql('CREATE INDEX IDX_C2521BF182D40A1F ON conversations (workspace_id)');
        $this->addSql('COMMENT ON COLUMN conversations.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN conversations.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN conversations.closed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT FK_C2521BF1E05387EF FOREIGN KEY (assistant_id) REFERENCES assistants (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT FK_C2521BF182D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // create conversation turns table
        $this->addSql('CREATE TABLE conversation_turns (id VARCHAR(36) NOT NULL, conversation_id VARCHAR(36) NOT NULL, role VARCHAR(255) NOT NULL, prompt_type VARCHAR(255) NOT NULL, provider_name VARCHAR(80) DEFAULT NULL, model VARCHAR(160) DEFAULT NULL, content JSON NOT NULL, prompt_tokens INT DEFAULT NULL, completion_tokens INT DEFAULT NULL, metadata JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E74018019AC0396 ON conversation_turns (conversation_id)');
        $this->addSql('CREATE INDEX idx_conversation_turns_conversation_created_at ON conversation_turns (conversation_id, created_at)');
        $this->addSql('COMMENT ON COLUMN conversation_turns.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE conversation_turns ADD CONSTRAINT FK_E74018019AC0396 FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // drop conversation history tables
        $this->addSql('ALTER TABLE conversation_turns DROP CONSTRAINT FK_E74018019AC0396');
        $this->addSql('ALTER TABLE conversations DROP CONSTRAINT FK_C2521BF1E05387EF');
        $this->addSql('ALTER TABLE conversations DROP CONSTRAINT FK_C2521BF182D40A1F');
        $this->addSql('DROP TABLE conversation_turns');
        $this->addSql('DROP TABLE conversations');

        // remove assistant provider defaults
        $this->addSql('ALTER TABLE assistants DROP default_provider');
        $this->addSql('ALTER TABLE assistants DROP default_model');
    }
}
