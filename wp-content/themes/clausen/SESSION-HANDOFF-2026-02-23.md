# TCLAS Session Handoff — 23 February 2026

## Project Overview
WordPress site at `/Applications/MAMP/htdocs/tclas-local/` — custom theme: `clausen` (v1.1, "Terres Rouges").
MAMP MySQL: mysql80 at `/Applications/MAMP/Library/bin/mysql80/bin/mysql`, DB: wp-tclas, user: root/root.
No WP-CLI available. No Python. Use MAMP mysql binary for DB work.

---

## What Was Completed This Session

### 1. About Page — Header Fix + 2:1 Layout (DONE)
**Files modified:**
- `page-templates/page-about.php` — Full rewrite:
  - Header: `--orpale` → `--ardoise`, `container` → `container-tclas`, added `tclas-eyebrow--light`
  - Merged two separate `<section>` blocks into one with a `tclas-about-layout` CSS grid
  - Board of directors moved into `<aside class="tclas-about-layout__sidebar">`
  - Board query unchanged: `WP_Query(['post_type' => 'tclas_board', ...])`
- `assets/css/main.css` — Added after `.tclas-page-header__title` (lines 670–684):
  - `.tclas-about-layout` — 2:1 CSS grid (`2fr 1fr`, 3rem gap)
  - `.tclas-board-grid` — vertical flex stack
  - `.tclas-board-card` — horizontal flex (52px circular photo + stacked info)
  - `.tclas-board-card__photo/img/info/name/role/email` — full card styling
  - Responsive: stacks at ≤768px

**Verified in browser:** Header correct, 2:1 layout working, 4 board members displaying with circular placeholders.

### 2. Full Site QA Audit (DONE)
Crawled every page accessible to a non-member. Generated:
- **HTML report:** `QA-AUDIT-2026-02-23.html` in the theme root
  - 21 findings across Critical/High/Medium/Low/Accessibility
  - Each finding has severity badge, description, suggested fix, and annotation box
  - Includes style guide reference, page-by-page summary table, and priority action table
- User has not yet annotated/reviewed the report

---

## What Was Completed in Prior Sessions

### Quiz Toggle (Section 2 — DONE)
- `luxembourg-citizenship-quiz/lcq-quiz.js` — v1.4:
  - `start` step: checkbox "I was born before 1969" (hint text removed)
  - `generation_loop`: pill toggle (`role="switch"`, `aria-checked`), defaults true for first gen, false for subsequent
  - `evaluateEligibility()`: checks `child.bornBefore1969` boolean
- `luxembourg-citizenship-quiz/lcq-styles.css` — toggle CSS added
- `luxembourg-citizenship-quiz/luxembourg-citizenship-quiz.php` — v1.4, localized lcqData

### Events Page Styling (DONE)
- `assets/css/tribe-events.css`:
  - Month/Day view tabs hidden (only LIST visible)
  - Single event page: 2-column grid (240px sidebar | 1fr description)
  - All single-event rules scoped under `.tribe-events-single` to avoid list-view conflicts
  - Responsive: stacks at ≤768px

### Prior QA Fixes (All 12 from earlier audit — DONE)
1. `inc/ancestor-map.php` — `'private'` → `'hidden'` (Critical: visibility check)
2. `page-templates/page-directory.php` — `container` → `container-tclas`
3. `footer.php` — Luxembourgish motto wrapped in `<span lang="lb">`
4. `inc/template-functions.php` — `tclas_ltz()` gets `<span lang="lb">` wrapper; `<abbr>` gets `tabindex="0"`
5. `lcq-styles.css` — `border-radius: var(--radius, 8px)` on buttons; dual-ring focus
6. `assets/css/main.css` — `z-index: -1` on watermark; 44px min-height on profile links
7. `inc/enqueue.php` — Updated comment; `wp_add_inline_style()` for lion SVG path
8. `page-templates/page-my-story.php` — i18n-wrapped social labels

### Global CSS (Section 1 — DONE)
- `--radius: 8px`, `--c-gold: #D4AF37`, `body { font-size: 1.125rem }`
- Footer column titles: `var(--c-or-pale)` (was crimson)
- Dual-ring focus: `outline + box-shadow` pattern
- Lion watermark: `body::after` fixed positioned, `z-index: -1`
- `inc/ltz-tagger.php` — auto-wraps Lux terms in `<span lang="lb">`

### Other Completed Items
- Member profiles (`page-member-profile.php`) with `tclas_lux_greeting()`
- My Story form with bio + social fields
- Directory cards link to profile pages
- Newsletter archive (`page-newsletter-archive.php`) with issue grouping
- `tclas_department` taxonomy with fixed terms
- Commune profile layout CSS (`.tclas-commune-layout`)

---

## Active Style Guide

- **Language:** U.S. English, AP style (Associated Press Stylebook)
- **Citations:** ASA format (American Sociological Association)
- **Dates/times:** Spell out months and days; "7 p.m." not "7:00 pm"; "at" not "@"; en-dash for ranges
- **Typography:** No ampersands in running text
- **Blog posts:** Option to hide author byline per individual post

---

## QA Audit Findings Summary (from today's crawl)

### Critical
1. **`/join/` page is empty** — All conversion CTAs point here (hero "Join us", nav "Membership", tier buttons, CTA "Join TCLAS"). Dead end. PMPro levels at `/membership-levels/` instead.
2. **`/contact/` page is empty** — Footer links to it twice. No form, no email, nothing.
3. **Nav labels swapped** — "Membership" → `/join/` (empty). Red "Join" → `/membership-levels/` (raw PMPro table). Backwards.

### High
4. **Header brand creates 3 orphan links** — `the_custom_logo()` inside `<a class="tclas-brand">` causes browser to split into empty fragments. Two links with no accessible name; brand text orphaned outside any link. Fix: use `wp_get_attachment_image()` instead of `the_custom_logo()`.
5. **About page has placeholder content** — "afasdfasdfasdfasdfadfasdfas" — needs real copy.
6. **Large empty illustration placeholders on homepage** — Sections 3 (About) and 5 (Membership) render big light-blue boxes.
7. **`container` vs `container-tclas` inconsistency** — `page.php` and `front-page.php` use Bootstrap `.container`, rest of site uses `.container-tclas`.
8. **News archive header doesn't match pattern** — No eyebrow, different hero style vs Events/Resources/About.

### Medium
9. Homepage tier "Join as..." buttons all hit empty `/join/?level=X`
10. `/membership-levels/` is unstyled raw PMPro table
11. Footer newsletter form doesn't render (Mailchimp not configured?)
12. Red pin icon on every post card (all appear "sticky")
13. Generic pages (Login, Contact, Join) lack themed headers
14. Date/time format doesn't follow AP style ("@" instead of "at", "pm" not "p.m.")

### Low
15. "1 min read" on every post
16. Mobile hamburger no close affordance (☰ doesn't toggle to ×)
17. Footer Organisation column sparse (2 links)
18. Facebook link opens new tab without indicator

### Accessibility
19. Two empty brand links in header (screen reader issue)
20. Luxembourgish strings in templates missing `lang="lb"` (Wëllkomm, Mir sinn hei)
21. Illustration placeholders may confuse assistive tech (alt text on empty divs)

---

## What's Next (Not Yet Started)

### Immediate (from QA audit)
User needs to review/annotate `QA-AUDIT-2026-02-23.html`, then we implement fixes based on their priorities. The suggested order:
1. Fix `/join/` page (add PMPro shortcode or redirect)
2. Swap nav labels
3. Real About page content (copywriting)
4. Contact page content
5. Fix header brand nested links
6. Hide illustration placeholders
7. Standardize `container-tclas`
8. Style News archive header
9. Configure newsletter form
10. Style `/membership-levels/`

### From Original Handoff Plan (Sections 3, 5)
- **Section 3 — Commune Profiles:** Enable `tclas_commune` taxonomy as public, create `taxonomy-tclas_commune.php` template, LOD.lu audio integration, map tile update (CartoDB Positron, gold markers), popup links to commune profiles
- **Section 5 — Editorial Engine:** `tclas_department` taxonomy terms already created, ACF fields for `tclas_issue_date` and `tclas_issue_order` need to be added to posts, newsletter archive page template exists but may need content

### Prerequisites Still Pending
- P1: Brando Sans Adobe Fonts kit ID
- P3: Forvo API key (LOD.lu primary, Forvo fallback)
- P2: Lion SVG is uploaded and working

---

## Key File Locations

| File | Purpose |
|------|---------|
| `page-templates/page-about.php` | About page (just fixed: 2:1 grid + board sidebar) |
| `page-templates/page-directory.php` | Member directory |
| `page-templates/page-member-profile.php` | Member profile view |
| `page-templates/page-my-story.php` | Edit profile form |
| `page-templates/page-newsletter-archive.php` | Loon & Lion archive |
| `page.php` | Generic page template (needs `container-tclas` fix) |
| `front-page.php` | Homepage (needs `container-tclas` fix) |
| `header.php` | Global header (brand link bug) |
| `footer.php` | Global footer |
| `assets/css/main.css` | Primary stylesheet |
| `assets/css/tribe-events.css` | TEC overrides (list + single event) |
| `inc/template-functions.php` | `tclas_ltz()`, `tclas_lux_greeting()`, helpers |
| `inc/ltz-tagger.php` | Auto Luxembourgish `lang="lb"` tagging |
| `inc/enqueue.php` | Script/style loading |
| `inc/ancestor-map.php` | Commune aggregate map |
| `luxembourg-citizenship-quiz/lcq-quiz.js` | Quiz engine (v1.4) |
| `luxembourg-citizenship-quiz/lcq-styles.css` | Quiz styles |
| `QA-AUDIT-2026-02-23.html` | Full QA audit report (annotatable) |

---

## CSS Architecture Notes

- **Design tokens:** `--c-crimson`, `--c-ardoise` (#0A2540), `--c-or-pale`, `--c-gold` (#D4AF37), `--c-border`, `--c-muted`, `--c-body-text`, `--radius` (8px)
- **Container:** Always use `.container-tclas` (max-width: 1200px) — NOT Bootstrap `.container`
- **Page headers:** `.tclas-page-header.tclas-page-header--ardoise` + `.tclas-eyebrow.tclas-eyebrow--light` + `.tclas-page-header__title`
- **TEC overrides:** All single-event CSS scoped under `.tribe-events-single` ancestor to avoid list-view conflicts
