This is a php/symfony project. We work with symfony recommended best-practices.
This project uses Doctrine ORM.
You are NOT allowed to downgrade package versions.
We use React and Tailwind for frontend. 
Instead of `php bin/console` you always execute `./console` (it executes inside docker).
You run all `./console`, `npm`, tests, and other commands with escalation. Your sandbox has lots of restrictions.
Frontend is located in `assets/`
Render React mounts from Twig templates in `templates/app`.
After each task, you git add and commit your changes (with escalation or it fails).
If you git status and see changes you didn't make, you DO NOT rollback those changes.
You write great commit messages, always, with thorough explanations of what changed.
When you finish a task, if decisions were made, we document these decisions in AGENTS.md. This is meant to avoid 'doing the same thing in different ways' throughout the application. We prefer to document in AGENTS.md file in the folder where it's appropriate (e.g. entity documentation in src/Entity/AGENTS.md; general frontend documentation in assets/AGENTS.md; overall project decisions in the project root/AGENTS.md).

- New accounts automatically receive a personal workspace named using the pattern `{FirstName}'s Workspace`; if the first name already ends with `s`, we omit the extra `s` (e.g. `James' Workspace`).

Authentication is handled through Symfony's session-based security guard. JSON login lives at `/api/auth/login`, and React talks to it with fetch requests that send/receive JSON while including credentials. Social login uses KnpU OAuth2 Client bundle with Google; add new providers via the same bundle.
You never create migrations manually, you always execute `./console doctrine:migrations:diff`
This is an SPA, we don't create twig templates, but react pages and setup a new route in react. All backend routes are API endpoints.
This project is in pre-production, so we don't need to keep backwards compatibility with existing data, we can drop everything and rebuild. We don't need fallback code for anything either.
Things should never fail silently. Make errors obvious so we can fix them.

We write backend and frontend tests whenever it's smart to do so. We don't write them for useless things with no logic (e.g. DTOs, entities), but we do write them to prevent regression and/or to make sure things work properly and business rules are tested.

IMPORTANT: Before reading, making changes to files, creating new files, deleting or moving files, you always check the closes AGENTS.md file and read it for further instructions.
Workspace is the canonical label for user groups that share assistants; new collaboration features should build on the Workspace / WorkspaceMembership / WorkspaceInvite stack.

Dev Vite server detection checks both `localhost` and Docker host gateways by default. If you need a custom dev server address, set `VITE_DEV_SERVER_PING` for the Symfony container probe and `VITE_DEV_SERVER_PUBLIC_URL` for the URL injected into templates.
- Platform admin tooling lives under `/admin`; protect new endpoints with `ROLE_ADMIN` and surface data through `AiAdminDashboardBuilder` so analytics remain consistent.
- Use the `app:user:promote` CLI (`./console app:user:promote user@example.com`) to grant roles such as `ROLE_ADMIN`; keep role elevation logic in that command instead of scattering repository updates.
