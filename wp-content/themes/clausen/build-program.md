# Build Program — TCLAS Site Reconciliation

_Updated 2026-03-11_

**Copy + UI review with Rebecca**: March 12, 2026
**Board preview deadline**: March 22, 2026
**Soft launch deadline**: April 18, 2026

---

## Phase summary

| Phase | Status | Completed | Notes |
|-------|--------|-----------|-------|
| 0. Decisions | ✅ Done | Mar 1 | All 8 open decisions resolved |
| 1. Foundation | ✅ Done | Mar 3 | Nav, footer, ACF fields, page stubs |
| 2. Homepage | ✅ Done | Mar 7 | Hero slideshow, mission, newsletter preview, quiz CTA, join bar |
| 3. Public pages | ✅ Done | Mar 8 | Citizenship (quiz v2.0), Join, MSP+LUX, Ancestry |
| 4. Events + Newsletter | ✅ Done | Mar 8 | Events archive, featured event, past events, newsletter sticky subnav |
| 5. Content + polish | **Pending** | — | Stub page content (FAQ, Mission, History, Contact, Financials) — **user-authored** |
| 6. Launch prep | **Pending** | — | Final sign-off, DNS, staging, production config |

**Phases 0–4 are complete.** The remaining work is content authoring (Phase 5) and production deployment (Phase 6).

---

## Phase 0 — Decisions ✅ COMPLETE (Mar 1)

All 8 decisions resolved. See `figma-gap-analysis.md §14` for the full log.

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

## Phase 5 — Content + Polish (target: Apr 5–11)

### User-authored content (critical path)
- FAQ page content (`/faq/`)
- Mission page content (`/about/mission/`)
- History page content (`/about/history/`)
- Contact page content (create page)
- Financials page content (`/about/financials/`)

### Dev tasks (when needed)
- Cross-browser QA: Chrome, Firefox, Safari, Edge
- Mobile/tablet QA at 375px, 768px, 1024px
- Accessibility fixes from `AUDIT-2026-03-05.md`
- Performance review

---

## Phase 6 — Launch Prep (target: Apr 12–17)

- Final content sign-off
- Production deployment to SiteGround
- Brevo API key + form setup
- FuseWP install for PMPro ↔ Brevo sync
- WP Mail SMTP OAuth configuration
- Complianz GDPR review
- The SEO Framework OG/Twitter Card verification
- Site Kit by Google activation
- Final staging smoke test

---

## Critical path to launch

| Blocker | Deadline | Owner |
|---------|----------|-------|
| MSP+LUX copy + resource URLs | Mar 12 | Matthew |
| Ancestry step copy + resource links | Mar 12 | Matthew |
| Citizenship pathways copy | Mar 12 | Matthew |
| Volunteer description | Mar 12 | Matthew |
| Department icons for Browse by Topic | Apr 3 | Matthew |
| Stub page content (FAQ, Mission, etc.) | Apr 11 | Matthew |
| GiveWP setup | Apr 11 | Matthew |
| Brevo config (API key, form, lists) | Apr 12 | Matthew |
| Production hosting ready | Apr 12 | Matthew |

---

## Post-launch backlog

See `outstanding-items.md` §5 (Design & UX Backlog) and §6 (Parking Lot) for deferred items including:
- Homepage navbar branding for first-time visitors
- Modern Footnotes styling
- PMPro Gift Memberships
- Member messaging plugin
- Map match email alerts
- CTA library CPT

---

*Originally created 2026-03-03. Updated 2026-03-08 to reflect all completed phases.*
