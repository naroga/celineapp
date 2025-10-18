# Component Guidelines

These specifications translate the brand foundations into reusable interface primitives. Reference Tailwind utility classes when implementing React components in `assets/react`.

## Buttons

- **Primary:** `bg-[var(--accent-primary)] text-slate-950 font-semibold px-4 py-2 rounded-md shadow-[0_8px_24px_-18px_rgba(58,209,194,0.8)] transition`. Hover darkens by 6% (`bg-teal-400/90`), focus adds teal glow (`focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-teal-300 focus-visible:ring-offset-slate-950`).
- **Secondary:** `bg-transparent border border-slate-700 text-slate-200 px-4 py-2 rounded-md`. Hover uses `border-slate-500 bg-slate-900`.
- **Ghost:** text-only with `px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-900/50`.
- Icons align left with `gap-2`. Use 16px icons (`size-4`) to keep density tight.
- Disable state reduces opacity to 45% and removes shadow; never shift layout.

## Inputs & Forms

- Text inputs: `bg-slate-950 border border-slate-800 focus:border-teal-400 focus:ring-0 rounded-lg px-3 py-2 text-slate-100`. Provide subtle inset shadow (`shadow-inner shadow-black/40`).
- Labels use `text-sm font-medium text-slate-200` with 4px spacing from input.
- Helper/error text: `text-xs text-slate-400` or `text-rose-400`.
- Group related fields in `grid grid-cols-2 gap-4` on desktop; collapse to single column on mobile.
- Toggle switches: horizontal, `bg-slate-700` track, `bg-teal-400` when active, 32px width, 16px height.

## Cards & Panels

- Container: `bg-[var(--bg-elevated)] border border-slate-800 rounded-xl p-5`. Optional `shadow-[0_16px_48px_-28px_rgba(0,0,0,0.75)]`.
- Header area uses `flex items-center justify-between gap-4`. Titles at `text-base font-semibold`.
- Card sections use `divide-y divide-slate-800`. Reduce padding inside sections to `py-4`.
- Mini cards (e.g., metrics) use `p-4 rounded-lg border border-slate-800 bg-slate-950`.

## Navigation

- Top bar: 56px height, `bg-slate-950/80 backdrop-blur-md border-b border-slate-900`.
- Left rail (optional): 64px width icons-only or 220px expanded. Use accent highlight bar for active item (`before:absolute before:left-0 before:top-1 before:h-8 before:w-1 before:bg-teal-400`).
- Breadcrumbs: `text-xs text-slate-400`, accent the current item with `text-slate-100`.

## Tables & Data Lists

- Table wrapper: `bg-slate-950 border border-slate-900 rounded-xl overflow-hidden`.
- Headers: `bg-slate-900/80 text-xs uppercase tracking-wide text-slate-400`.
- Rows: `hover:bg-slate-900/60` with `border-b border-slate-900`. Use zebra striping only if > 12 rows.
- Empty state row spans all columns with centered messaging.

## Chat & Assistant Elements

- Conversation container: `space-y-4` with `max-w-3xl` centered.
- User bubbles: `bg-slate-950 border border-slate-800 rounded-2xl rounded-tr-md px-4 py-3`.
- Assistant bubbles: `bg-[var(--bg-elevated)] border border-teal-500/40 rounded-2xl rounded-tl-md px-4 py-3`.
- Inline code uses `bg-slate-900 font-mono text-sm px-2 py-1 rounded`.
- Streaming indicator: three-dot pulse using `bg-teal-400/70` dots at 6px diameter.

## Modals & Overlays

- Backdrop: `bg-black/70 backdrop-blur-sm`. Use fade-in 200ms.
- Dialog: `max-w-2xl w-full bg-[var(--bg-elevated)] border border-slate-800 rounded-xl p-6`.
- Header layout: `flex justify-between items-start gap-4`. Include close icon ghost button.
- Footer: compress spacing (`pt-4 space-x-3`). Primary action right-aligned.

## Tabs & Filters

- Tab list: `flex gap-2 bg-slate-950 border border-slate-900 rounded-lg p-1`.
- Active tab: `bg-teal-400/15 text-slate-100 border border-teal-400/40`.
- Inactive tab: `text-slate-300 hover:text-slate-100`.
- Filter pills: `rounded-full border border-slate-700 px-3 py-1 text-xs font-medium`.

## Feedback

- Success banners: `bg-emerald-500/15 border border-emerald-500/40 text-emerald-200`.
- Warning: `bg-amber-500/15 border border-amber-500/40 text-amber-200`.
- Error: `bg-rose-500/15 border border-rose-500/40 text-rose-200`.
- Toasts: 320px width, anchored top-right, slide-in-right 200ms.

## Data Visualization

- Use narrow color set: teal for primary series, indigo for comparative, slate gradients for background.
- Axes and grid lines in `#1F2328`. Labels in `text-slate-300` with `text-xs`.
- Chart backgrounds match `bg-slate-950` with 16px padding.

