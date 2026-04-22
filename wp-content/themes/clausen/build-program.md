# Build Program — TCLAS Site Reconciliation

_Updated 2026-04-22_

**Status:** Phases 0–4 complete (Mar 1–11, 2026). Phases 5–6 (original content/launch-prep plan) were **superseded** on 2026-04-22 when the board approved a pared-down **May 9, 2026 MVP launch**. See `/Users/matthew/Local Sites/tclas-dev/BOARD_LAUNCH_BRIEF.md` Phase 0 for the current plan.

Active branch for MVP work: `launch/mvp-may-9`.

---

## Phase summary

| Phase | Status | Completed | Notes |
|-------|--------|-----------|-------|
| 0. Decisions | ✅ Done | Mar 1 | All 8 open decisions resolved |
| 1. Foundation | ✅ Done | Mar 3 | Nav, footer, ACF fields, page stubs |
| 2. Homepage | ✅ Done | Mar 7 | Hero slideshow, mission, newsletter preview, quiz CTA, join bar |
| 3. Public pages | ✅ Done | Mar 8 | Citizenship (quiz v2.0), Join, MSP+LUX, Ancestry |
| 4. Events + Newsletter | ✅ Done | Mar 8 | Events archive, featured event, past events, newsletter sticky subnav |
| 5. Content + polish | ⛔ Superseded | — | Rolled into May 9 MVP scope — most Phase-5 pages (FAQ, Mission, History, Financials) are deferred past launch |
| 6. Launch prep | ⛔ Superseded | — | Replaced by MVP Phase 0 launch plan (see `BOARD_LAUNCH_BRIEF.md`) |

Phases 0–4 shipped the full-vision foundation, which gives us the luxury of cutting scope for May 9 without rebuilding anything.

---

## Phase 0 — Decisions ✅ COMPLETE (Mar 1)

All 8 decisions resolved.

| ID | Decision | Outcome |
|----|----------|---------|
| OD-1 | Ancestry map | **Live read-only Leaflet embed** (no auth required — aggregate data only) |
| OD-2 | Tier cards on `/join/` | **Same page**, scroll anchor from Membership track card |
| OD-3 | `/quiz/` URL | **301 redirect** to `/citizenship/` |
| OD-4 | Testimonials | **One shared ACF repeater** (`testimonials` in Theme Options) |
| OD-5 | Homepage stats | **ACF option fields** (`homepage_stats` repeater) |
| OD-6 | Newsletter tab filtering | **Client-side JS** (no page reload) |
| OD-7 | Donate track | **Deferred** — page template + GiveWP integration built; admin needs to install GiveWP |
| OD-8 | `/about/` in nav | **Keep** — nav is 7 items: About · Events · Newsletter · MSP+LUX · Ancestry · Citizenship · Join Us |

---

## Phase 1 — Foundation ✅ COMPLETE (Mar 3)

- Updated primary nav to 7 items
- Moved "Member Log In" to standalone header element
- Footer: Instagram + LinkedIn ACF fields, conditional social icons, renamed columns
- New ACF fields: hero photos + credits, social URLs, homepage stats repeater, testimonials repeater
- Page stubs created for all target pages

---

## Phase 2 — Homepage ✅ COMPLETE (Mar 7)

- Split dual-photo hero with accumulating greeting animation ("Bonjour." → "Hello." → "Moien.")
- Hero slideshow with slide-over transitions and reduced-motion fallback
- Mission statement section
- Newsletter preview (latest issue TOC + featured image)
- Events section ("Happening Soon")
- Citizenship quiz CTA (ardoise band)
- "Join the Community" bar (gold)
- Testimonials and stats sections built (content deferred — org too young)

---

## Phase 3 — Public Pages ✅ COMPLETE (Mar 8)

### 3.1 MSP+LUX ✓
- `page-msp-lux.php` — 3 pillar cards, connection info with counters, resource links
- `msp-lux-counters.js` — animated stat counters

### 3.2 Citizenship ✓
- `page-citizenship.php` — quiz embed + pathways section
- Quiz v2.0 full UX redesign: lineage tracing, smart 1969 skip, lineage tracker sidebar with real-time family tree
- `/quiz/` → `/citizenship/` redirect

### 3.3 Join ✓
- `page-join.php` — referral banner for members, membership + volunteer tracks, soft perks, tier cards

### 3.4 Ancestry ✓
- `page-ancestry.php` — two-column layout (steps + sticky map), public commune map embedded, resource links
- Commune profiles: three-column layout, LOD.lu audio, municipality subtitles, external links in Gemeng row
- Communes A-Z index at `/member-hub/ancestral-map/commune/`
- 583 commune terms enriched with municipality, Luxembourgish names, coordinates, Wikipedia + official website URLs

---

## Phase 4 — Events + Newsletter ✅ COMPLETE (Mar 8, enhanced Mar 11)

- Custom TEC archive: `default-template.php` overhaul with featured event hero, upcoming grid, past events
- `tclas_get_past_events()` helper
- Members-only badge injection in list view
- Newsletter sticky subnav (`tclas-nl-subnav`, `initNlSubnav()` IntersectionObserver)
- Browse by topic section (pending department icons — user task)

### Membership section UX enhancements (Mar 11)
- **Member pages**: Simplified hub dashboard, added breadcrumbs to all member pages (Home › Member hub › page)
- **Member nav**: Added logout link, Documents page, "My Profile" instead of "My Story", renamed "Dashboard" → "Member hub" in main nav
- **Forum URLs**: Changed from `/forums/` → `/member-hub/forums/` for URL structure consistency
- **Newsletter topic pages**: Created `taxonomy-tclas_department.php` archive with hierarchical breadcrumbs, inline English descriptions
- **Article cards**: Restructured `card-article-nl.php` from horizontal to vertical layout (image on top); displays issue date + byline; matches post card component structure

---

## Phases 5 & 6 — ⛔ Superseded by May 9 MVP (2026-04-22)

The original Phase 5 (content authoring for FAQ, Mission, History, Contact, Financials) and Phase 6 (full production deployment) were replaced by the May 9 MVP plan. The MVP launches five pages — Home, Events, About, Contact, and Legal — with everything else held for Phase 2.

**See** `/Users/matthew/Local Sites/tclas-dev/BOARD_LAUNCH_BRIEF.md` **Phase 0** for:
- MVP scope
- Plugin activation/deactivation posture
- Branch and file locations
- Open items before May 9
- Legal-page drafts in `/Users/matthew/Local Sites/tclas-dev/launch-content/`

The original Phase 5/6 checklist (FAQ, Mission, History, Financials content; GiveWP; Brevo; FuseWP; Complianz; full staging QA) remains the target for **Phase 2 / post-May-9** and is described in `BOARD_LAUNCH_BRIEF.md` PART 2.

---

## Post-launch backlog

Deferred items (revisit after the May 9 MVP ships):
- Homepage navbar branding for first-time visitors
- Modern Footnotes styling
- PMPro Gift Memberships
- Member messaging plugin
- Map match email alerts
- CTA library CPT
- Full legal review of the four MVP legal pages
- Migration to role-based Google Workspace email aliases for contact routing

---

*Originally created 2026-03-03. Updated 2026-03-08 for completed phases. Updated 2026-04-22 for May 9 MVP pivot.*
