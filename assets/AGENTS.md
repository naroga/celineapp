Frontend React components belong in `assets/react/components`.
Tailwind global styles live in `assets/styles/app.css`.
Register new React entrypoints through `assets/app.jsx`.
We do not use inline `style` tags, we only use tailwind classes.
UI guidelines live in `docs/ui-guidelines/README.md`, check them.
Run the Vite dev server with `make watch` (stop it with `make watch-stop`).
SPA routing uses `react-router-dom`; register pages under `assets/react/pages` and wire them in `assets/react/components/App.jsx`.
Shared HTTP helpers live in `assets/react/lib/apiClient.js`; prefer the `apiFetch` wrapper for authenticated requests.
Workspace-specific API helpers live in `assets/react/lib/workspaceApi.js` and should be used for future workspace flows.
- Assistant flows use `assets/react/lib/assistantApi.js`; reuse those helpers for generation or CRUD instead of duplicating fetch logic.
- Assistant editing lives in `assets/react/pages/AssistantSettingsPage.jsx` and is reachable via `/workspaces/:workspaceId/assistants/:assistantId`; surface edit links through that route.
Authenticated pages render within `assets/react/components/layout/AuthenticatedLayout.jsx`, which includes the global navbar; mount new protected routes beneath that layout.
Authentication state is managed by `AuthProvider` in `assets/react/components/auth`; reuse its `useAuth` hook instead of duplicating state.
- Admin-only SPA routes must wrap page components with `RequireAdmin` and fetch data via helpers in `assets/react/lib/adminApi.js`.
- Keep AI analytics UI under `AdminAiDashboardPage.jsx` and update the shared dashboard instead of creating parallel admin screens.
