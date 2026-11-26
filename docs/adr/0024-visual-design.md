# ADR 0024: Visual design and navigation

Status: Accepted (2026-11-26). The mascot artist can still ask for changes. Expect changes during the build.

## Context

The design session decided the colours, the navigation, the icons and the mascot before the first UI work (Phase 3). The owner looked at visual options and chose one for each topic. The mascot artist gets a draft brief and can propose changes.

## Decision

### Colour palette: "Pond"

| Token | Light | Dark |
|---|---|---|
| Ground | `#F4F8F7` | `#0B1715` |
| Card | `#FFFFFF` | `#132321` |
| Text | `#102A27` | `#E3EFEC` |
| Muted text | `#4A615D` | `#9DB3AE` |
| Primary | `#0F766E` | `#2DD4BF` |
| Text on primary | `#FFFFFF` | `#0B1715` |
| Accent (fill only) | `#F5C518` | `#FACC15` |
| Minus (penalty) | `#B91C1C` | `#F87171` |

- All text colours have a contrast of 4.5:1 or more (WCAG AA).
- The accent is a fill colour. Do not use it for text on the light theme.
- A credit change always shows its sign (+ or −), not only its colour.

### Type

- Headings: Nunito (700, 800). Body: Inter (400, 500, 600).

### Navigation

- Today has no tab bar. The cards are the menu. The header has Admin (admins only) and Settings, each with an icon and a visible label.
- All other pages have a bottom tab bar: Today, Focus, Tasks, Habits, More.
- More holds Nutrition, Rewards, Credits history, Settings, Admin and Log out.
- At 1024 px and wider, a left sidebar replaces the tab bar.
- Each page has its own URL, so the browser Back button and bookmarks work.

### Icons

- Google Material Symbols, Rounded style, weight 400, from the npm package `@material-symbols/svg-400` (Apache 2.0).
- Outline icons by default. The filled form marks the active tab.
- Slots: credits `toll`, reward icons `redeem`, `coffee`, `sports_esports`, `movie`, `restaurant`, `flight`; tabs `home`, `timer`, `task_alt`, `event_repeat`, `more_horiz`; `nutrition`, `local_fire_department` (streak), `settings`, `shield_person` (admin).

### Mascot

- A duck. An artist draws it. The brief lists the files: app icons 512 and 192 px, a maskable icon 512 px (duck inside the centre 80 %), an Apple touch icon 180 px, a favicon (SVG, 32 and 16 px), a push badge 96 px, a header logo and empty-state art.
- Until the art is ready, the placeholder is `mdi:duck` from Material Design Icons (Apache 2.0).

### PWA install and updates

- In the phone browser, the app shows an install hint card. "Not now" hides it for 14 days. On iOS the card shows the "Add to Home Screen" steps.
- Desktop browsers show their own install button. The app adds nothing.
- New versions: `vite-plugin-pwa` with `registerType: 'prompt'`. The app shows "A new version is ready" with "Reload" and "Later". It does not reload by itself.
- The manifest `id`, `start_url` and `scope` do not change. A change of these values forces each user to install the app again.

## Alternatives

- Palettes "Lily pad" (green and amber) and "Evening pond" (blue and orange): kept in the brief for comparison.
- A tab bar on all pages, and a tab bar with a large centre Focus button: the Today cards already give one tap to each feature.
- Own drawn icons: more work, and less clear than a known icon set.
- Automatic update with reload: it can lose a form that the user has not sent.

## Consequences

- Phase 3 adds the colour tokens to Tailwind, the fonts, the layout, the tab bar, the sidebar, the install card and the update prompt.
- New npm dependency: `@material-symbols/svg-400`. Import only the icons that the app uses.
- The final mascot files replace the placeholder and the current `frontend/public/icon.svg`.
- An installed app can show an old icon or name for some days after a change. On iOS, only a new install shows the new icon.
- Empty states and the first-use flow are still open (design round 2).
