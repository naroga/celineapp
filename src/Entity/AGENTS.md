All entity IDs must be string length 36 with strategy none; 
We generate ID values in the entity constructor with ramsey uuid v4;
We don't keep logic in entities, as they are mostly value-objects;
We use native PHP attributes where we can;
`App\Entity\User` is the canonical auth model; keep it thin (identifiers, profile names, OAuth ids, timestamps).

Workspaces are represented by `App\Entity\Workspace` with memberships via `App\Entity\WorkspaceMembership` and invites via `App\Entity\WorkspaceInvite`.
- Workspace roles are limited to `owner`, `admin`, and `member`; only owners/admins may issue invites.
- Workspace invite tokens are 14-day TTL (configurable via `app.workspace_invite_ttl`) and always lowercase email addresses.
- Always route new access through `WorkspaceManager` to enforce membership checks and invite flows.

Assistants live in `App\Entity\Assistant`; each assistant belongs to a workspace, keeps contact details, and enforces unique email per workspace so we can’t register the same assistant twice.
- Assistants can store default AI provider/model hints via `setDefaultProvider`/`setDefaultModel`; keep values lowercase/trimmed before persisting.
- Assistant gender is stored as lowercase `male` or `female`; normalise input before calling the entity constructor or setters.
- Conversation history is persisted via `App\Entity\Conversation` and `App\Entity\ConversationTurn`; never splice data directly—use the `ConversationManager` service to append turns and seed playbooks.
