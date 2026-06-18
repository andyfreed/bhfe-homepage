# BHFE Homepage

Self-contained front page for **bhfe.com**. Takes over the static front page and renders a
credential **finder** (expand-in-place drawers), a **multi-license** picker, **promo**,
**benefit cards**, and a **browse row** — replacing the old ACF flexible-content homepage.

Theme-independent: it hooks `template_include` and ships its own front-page-only CSS/JS, so it
survives theme changes. Install/update via **WP Pusher**, same as the other BHFE plugins.

## What it does
- `template_include` (priority 99): on `is_front_page()`, serves `templates/front-page.php`
  (wrapped by the active theme's header/footer). Wins over the theme and the ACF builder.
- Enqueues `assets/homepage.css` + `assets/homepage.js` on the front page only
  (filemtime-versioned; Autoptimize aggregates them normally).
- All markup is in `includes/render.php` (`bhfe_hp_*` functions). No inline `<script>`
  (Autoptimize strips those); no inline styles.

## Hero = still editable in wp-admin
The hero reads the **ACF hero band** on the front page (heading, subheading, two CTAs,
background image), so staff can edit it without a developer. If ACF or the fields are absent
it falls back to built-in defaults. Everything below the hero is structured and lives in code.

## Verified data wiring
- "Full catalog" tiles → real credential category pages (`/courses/all-cpa-courses/`,
  `/courses/cfp-courses/`, `/eaotrp-courses/`, `/courses/all-iar-courses/`,
  `/courses/cima-cpwa-rma-courses/`, `/courses/all-cdfa-courses/`).
- Ethics tiles → `/courses/ethics-courses-for-accountants/?credit_type[]=<slug>` (portable slugs).
- CPA ethics → state-gated; `<option>` values are this environment's `state` term IDs,
  generated at render time (sentinel "All"/"All States" terms excluded).
- Browse pills → `subject` term IDs resolved **by name at render time** (the catalog filters on
  term ID, not slug), with known local-ID fallbacks.
- **Multi-license** → submits `/courses/?credit_type[]=a&credit_type[]=b…`. The copy says
  "courses approved for **all** selected credentials", which is only correct once the catalog
  query **ANDs** (intersects) multiple `credit_type[]`. (BHFE is making that change; until it's
  live the catalog unions.)

## Not in this plugin (by design)
The site-wide `.screen-reader-text` hide rule and the WooCommerce loop price-table grid fix live
in the **theme** (`min/css/site-fixes.css`) because they apply everywhere, not just the homepage.

## Install (WP Pusher)
Repo `andyfreed/bhfe-homepage`, branch `main`, plugin file at the repo root. No subdirectory.
Activate; it takes over the front page immediately.
