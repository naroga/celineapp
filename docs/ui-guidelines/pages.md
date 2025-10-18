# Page Patterns

Define layouts that support the product journey while maintaining the dense, intelligent aesthetic.

## Application Shell

- **Header:** 56px height, contains product name left, primary actions right. Use translucent background (`bg-slate-950/80`) with 1px bottom border.
- **Workspace Layout:** two-column responsive grid. Left column hosts navigation or context panel (min 220px), right column contains primary content with `max-w-5xl`.
- **Padding:** `px-6` on desktop, `px-4` on tablet, `px-3` on mobile. Keep vertical spacing tight (`py-6` header, `py-5` content sections).
- **Status Bar:** optional row under header for environment indicators with `text-xs text-slate-400`.

## Dashboard

- Hero band: `flex justify-between items-center` with quick stats; avoid large hero imagery.
- Metrics cards: 3 or 4 column grid (`grid-cols-3 gap-4` or `grid-cols-4 gap-4`), each using mini card spec.
- Insights panel: vertical stack (`space-y-3`) of cards with timestamps and tags.
- Recent activity list: condensed table style; show 6–8 items per page to maintain density.

## Assistant Workspace

- Split view: conversation column (`max-w-3xl mx-auto`) with optional memory sidebar (`w-80`).
- Input dock: fixed bottom bar `bg-[var(--bg-elevated)]/95 border-t border-slate-800 px-4 py-3` with textarea and action buttons.
- Memory highlights: right sidebar cards summarizing current person context; use accent border on active memory.
- Suggested prompts appear as small ghost buttons above the input dock.

## Memory Management

- List view with filter row (tabs + search). Use sticky header to keep filters visible.
- Detail drawer slides from right (`max-w-lg`) using the modal rules; includes timeline of interactions.
- Editing forms use two-column grid on desktop, stacking on mobile.

## Settings

- Section navigation via left rail tabs. Each section uses cards for grouping.
- Toggle groups aligned in dense stacks (`space-y-2`). Place destructive actions at bottom with accent secondary color.
- Confirmation modals include summary of impacted memories or assistants.

## Authentication

- Keep to a single compact column (`max-w-sm`) centered; background uses gradient overlay `bg-[radial-gradient(circle_at_top,rgba(100,139,255,0.25),transparent_60%)]`.
- Headline `text-xl font-semibold text-slate-100`, subtext `text-sm text-slate-400`.
- Primary action button spans width; include progress indicator while submitting.

## Empty States & Loading

- For first-run experiences, display a concise headline (≤ 32 characters) and action button; include supporting copy ≤ 60 characters.
- Skeleton loaders use `animate-pulse bg-slate-900` with 6px radius; prefer skeletons over spinners for list views.
- Use teal accent spinner (`border-t-teal-400`) only for short blocking operations (< 2s).

## Responsiveness

- Breakpoints: mobile ≤ 640px, tablet 641–1024px, desktop ≥ 1025px.
- Collapse sidebars into drawers on tablet; preserve conversation width by stacking panels below.
- Ensure interactive touch targets remain ≥ 40px height even when overall density is tight.

