# UI Guidelines

These guidelines define the visual and behavioural foundations for the custom AI-assistant product so every surface feels lean, intelligent, and dependable.

## Brand Attributes

- **Mindset:** confident, insightful, discreet.
- **Personality:** warm-professional; pragmatic rather than playful.
- **Promise:** the assistant remembers context and anticipates needs without clutter.
- **Voice:** concise, direct sentences; verbs first; no marketing fluff.

## Visual Identity

### Color System

Use a graphite-first palette with subtle contrast shifts. Tailwind tokens are suggestions; extend the Tailwind config if the shades are missing.

| Token | Hex | Tailwind Reference | Usage |
| --- | --- | --- | --- |
| `bg.body` | `#121417` | `slate-950` base | App background |
| `bg.elevated` | `#181B1F` | custom shade | Cards, panels |
| `bg.sunken` | `#0D0F11` | `black` mix | Sidebars, headers |
| `border.default` | `#272B31` | `slate-800` | Dividers, outlines |
| `text.primary` | `#F5F7FA` | `slate-100` | Primary copy |
| `text.secondary` | `#A9B2C1` | `slate-400` | Helper copy, labels |
| `accent.primary` | `#3AD1C2` | `teal-300` | Primary actions, focus |
| `accent.secondary` | `#648BFF` | `indigo-400` | Secondary actions, links |
| `state.success` | `#2EBF82` | `emerald-400` | Positive feedback |
| `state.warning` | `#F5A524` | `amber-400` | Alerts, cautions |
| `state.error` | `#F45B69` | `rose-400` | Errors, destructive actions |

Guidelines:

- Keep contrast ratios ≥ 4.5:1 for text; shift the shade rather than pure white for large headings.
- A single accent per view is preferred; use secondary accent sparingly for supplemental actions.
- Gradients are acceptable only in hero illustrations and should stay within the accent family.

### Typography

- **Primary font:** Inter (sans-serif). Use Tailwind defaults (`font-sans`) with optical sizing on.
- **Scale:** adopt a tight modular scale; recommended sizes: `text-xs` for captions, `text-sm` body, `text-base` for primary body, `text-lg` for section titles, `text-xl` for page headings.
- **Weight:** use `font-semibold` for headings, `font-medium` for labels, `font-normal` for body.
- **Line-height:** keep `leading-5` to `leading-6` to maintain density; avoid `leading-loose`.

### Spacing & Layout Density

- Default spacing increments: `0.5rem`, `0.75rem`, `1rem`. Use tighter stacks (`space-y-2` / `space-y-3`) instead of large gaps.
- Section padding: `px-6 py-5` on desktop, `px-4 py-4` on mobile.
- Components should rarely exceed `max-w-5xl`; avoid edge-to-edge width—maintain a 64px outer gutter on desktop, 20px on mobile.
- Use subtle depth: `shadow-[0_12px_40px_-20px_rgba(0,0,0,0.6)]` for elevated panels; prefer border effects over heavy shadows.

### Imagery & Iconography

- Icon style: thin stroke, 1.5px weight, rounded edges. Prefer phosphor or lucide icon sets.
- Illustrations: abstract, geometric shapes in graphite, teal, indigo; no photography unless depicting interfaces.
- Empty states use minimal line art with at most two accent colors; pair with concise copy (≤ 12 words).

### Motion

- Transition duration defaults to `150ms`; extend to `200-250ms` for modals and larger transitions.
- Motion should indicate context switches (e.g., fade + slide for drawer) and should never bounce.
- Prefer `ease-out` for entry, `ease-in` for exit; avoid overshoot easing curves.

## Communication UX

- Responses highlight the assistant’s capability: start with the conclusion, follow with supporting detail.
- System prompts or tips use gray (`text.secondary`) text with a teal left border (`border-l-2 border-teal-400`).
- Notifications are toast-based, aligned top-right on desktop, full-width bottom on mobile.
- Confirmations are explicit: include an action verb and the affected entity (e.g., “Memory saved for Jordan”).

## Implementation Guidance

- Configure Tailwind to expose the palette tokens above via CSS custom properties (see `assets/styles/app.css` for entry point).
- Define reusable class compositions with Tailwind’s `@layer components` for buttons, cards, badges.
- Prefer CSS variables for background and text colors so dark mode adjustments remain centralized.

## Related Documents

- See `docs/ui-guidelines/components.md` for component-level specs.
- See `docs/ui-guidelines/pages.md` for page templates and layout guidance.

