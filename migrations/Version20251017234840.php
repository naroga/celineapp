<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017234840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_interactions (id VARCHAR(36) NOT NULL, workspace_id VARCHAR(36) DEFAULT NULL, assistant_id VARCHAR(36) DEFAULT NULL, conversation_id VARCHAR(36) DEFAULT NULL, context VARCHAR(120) DEFAULT NULL, prompt_type VARCHAR(255) NOT NULL, provider_name VARCHAR(80) NOT NULL, model VARCHAR(160) DEFAULT NULL, status VARCHAR(255) NOT NULL, error_code VARCHAR(160) DEFAULT NULL, error_message TEXT DEFAULT NULL, prompt_tokens INT DEFAULT NULL, completion_tokens INT DEFAULT NULL, total_tokens INT DEFAULT NULL, cost_cents INT DEFAULT NULL, metadata JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_839BD1382D40A1F ON ai_interactions (workspace_id)');
        $this->addSql('CREATE INDEX IDX_839BD13E05387EF ON ai_interactions (assistant_id)');
        $this->addSql('CREATE INDEX IDX_839BD139AC0396 ON ai_interactions (conversation_id)');
        $this->addSql('CREATE INDEX idx_ai_interactions_status_created_at ON ai_interactions (status, created_at)');
        $this->addSql('CREATE INDEX idx_ai_interactions_conversation_created_at ON ai_interactions (conversation_id, created_at)');
        $this->addSql('CREATE INDEX idx_ai_interactions_context ON ai_interactions (context)');
        $this->addSql('COMMENT ON COLUMN ai_interactions.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ai_interactions ADD CONSTRAINT FK_839BD1382D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ai_interactions ADD CONSTRAINT FK_839BD13E05387EF FOREIGN KEY (assistant_id) REFERENCES assistants (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ai_interactions ADD CONSTRAINT FK_839BD139AC0396 FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE ai_interactions DROP CONSTRAINT FK_839BD1382D40A1F');
        $this->addSql('ALTER TABLE ai_interactions DROP CONSTRAINT FK_839BD13E05387EF');
        $this->addSql('ALTER TABLE ai_interactions DROP CONSTRAINT FK_839BD139AC0396');
        $this->addSql('DROP TABLE ai_interactions');
    }
}
