# Build Program — TCLAS Site Reconciliation

**Board preview deadline**: March 22, 2026
**Soft launch deadline**: April 18, 2026
**Today**: March 1, 2026

---

## Milestones overview

| Phase | Dates | Goal |
|-------|-------|------|
| 0. Decisions | Mar 1–2 | Resolve all 8 open decisions; confirm asset availability |
| 1. Foundation | Mar 3–7 | Nav, footer, ACF fields, page stubs — framework everything needs |
| 2. Homepage | Mar 8–14 | Full homepage overhaul — the board's first impression |
| 3. Public pages sprint | Mar 15–21 | Citizenship, Join, MSP+LUX, Ancestry |
| **→ Board preview** | **Mar 22** | All public-facing pages working; Events/Newsletter in current state is OK |
| 4. Events + Newsletter | Mar 23–Apr 4 | Custom TEC template, newsletter sub-nav, Browse by Topic |
| 5. Content + polish | Apr 5–11 | Stub pages filled in, QA, cross-browser, mobile |
| 6. Launch prep | Apr 12–17 | Final sign-off, DNS, redirects, backup |
| **→ Soft launch** | **Apr 18** | |

---

## Phase 0 — Decisions ✓ COMPLETE (Mar 1)

All 8 decisions resolved. See `figma-gap-analysis.md §14` for the full log.

| ID | Decision | Outcome |
|----|----------|---------|
| OD-1 | Ancestry map | **Live read-only Leaflet embed** (no auth required — aggregate data only) |
| OD-2 | Tier cards on `/join/` | **Same page**, scroll anchor from Membership track card |
| OD-3 | `/quiz/` URL | **301 redirect** to `/citizenship/` |
| OD-4 | Testimonials | **One shared ACF repeater** (`testimonials` in Theme Options) |
| OD-5 | Homepage stats | **ACF option fields** (`homepage_stats` repeater) |
| OD-6 | Newsletter tab filtering | **Client-side JS** (no page reload) |
| OD-7 | Donate track | **Hold entirely** — ship `/join/` with Membership + Volunteer only |
| OD-8 | `/about/` in nav | **Keep** — nav is 7 items: About · Events · Newsletter · MSP+LUX · Ancestry · Citizenship · Join Us |

**Still needed from you before Phase 2:**
- Both hero photos (Minneapolis + Luxembourg City) in WP Media Library by **March 8**
- Testimonials content (name, year, location, quote, photo) — at least 3 rows
- Stats values confirmed (300+, 12+, 40, 150+ — or corrected numbers)

---

## Phase 1 — Foundation (Mar 3–7)

Low-risk groundwork. Everything else builds on top of this.

### Dev tasks

**Nav & footer (1 day)**
- Update WP primary menu to 7 items: About · Events · Newsletter · MSP+LUX · Ancestry · Citizenship · Join Us
- Move "Member Log In" in `header.php` to a standalone element outside the nav list
- Footer: add `instagram_url` + `linkedin_url` ACF fields, update `footer.php` to render all three social icons conditionally, rename column headings to "Explore" and "About"

**ACF new fields (0.5 day)**
- Theme Options additions:
  - `hero_photo_msp` (image)
  - `hero_photo_msp_credit` (text)
  - `hero_photo_lux` (image)
  - `hero_photo_lux_credit` (text)
  - `instagram_url` (URL)
  - `linkedin_url` (URL)
  - `homepage_stats` (repeater: rows of `stat_value` + `stat_label`)
  - `testimonials` (repeater: `name`, `member_since`, `location`, `photo`, `quote`) — shared by homepage + ancestry
- `tclas_department` term meta: `department_icon` — for Newsletter Browse by Topic (Phase 4)
- Note: `donation_url` field **not** added yet (Donate track held — OD-7)

**Page stubs** — ✓ being handled immediately (see user's follow-up request)

### User tasks this week
- Source / confirm availability of both hero photos (needed by Mar 8)
- Draft testimonials content (name, member-since year, location, quote, photo) — at least 3
- Confirm stat values

---

## Phase 2 — Homepage (Mar 8–14)

The single biggest piece of work. One week for `front-page.php` + all its partials.

**Dependency: hero photos must be in WP Media Library by March 8.**

### Dev tasks (ordered)

**Day 1-2: Hero section**
- Replace current single-illustration hero with split dual-photo layout
- New markup: two `<div>` halves (or `<picture>` + CSS clip) each pulling from new ACF fields
- Photo credit overlays (bottom corners)
- New copy: title, tagline, CTA buttons (static, no auth-conditional logic per Figma — confirm this)

**Day 2: Mission statement section**
- New section below hero: centered heading + paragraph
- Static copy, no ACF needed

**Day 3: Newsletter preview section**
- Pull the most recent newsletter post (latest `tclas_issue_date` meta)
- Left column: loop over that post's articles grouped by `tclas_department` taxonomy, rendering section label + article title + excerpt per row
- Right column: post featured image
- "Browse the newsletter archives →" link
- This replaces the current "Latest news & stories" section — remove the latter

**Day 3-4: Testimonials + Stats**
- Testimonials: render ACF `homepage_testimonials` repeater as 3-card grid
- Stats: 4-cell strip below testimonials (from ACF or hardcoded per OD-5)
- Replace current membership tier cards section (remove from homepage)

**Day 4: Citizenship quiz CTA section**
- Dark ardoise-teal band: heading, body text, "Check Your Eligibility" pill button → `/citizenship/`
- Replace current crimson Join CTA section

**Day 4-5: "Join the Community" CTA bar**
- Orange/gold background strip
- "Become a Member" + "Member Log In" buttons
- This is the new pre-footer section

**Day 5: Cleanup + events section**
- Events section: update eyebrow copy ("HAPPENING SOON"), confirm card layout matches new design language
- Remove Welcome/About illustration section
- Smoke test the full page

### User tasks this week
- Upload both hero photos and enter photo credits in ACF Theme Options
- Enter testimonial content in ACF Theme Options
- Enter/confirm stats values
- Confirm or provide copy for: mission statement, hero title/tagline, CTA label text, quiz CTA body text

---

## Phase 3 — Public Pages Sprint (Mar 15–21)

Four pages in one week. These are all medium complexity individually.

### Dev tasks

**Citizenship page (1.5 days)**
- Create `page-templates/page-citizenship.php`
- Assign to `/citizenship/` page in WP Admin
- Sections:
  1. Centered title + eyebrow header (light bg)
  2. Quiz embed: `[luxembourg_eligibility_quiz]` shortcode inside a styled container
  3. Legal disclaimer note
  4. "Pathways to Citizenship" section: 3 icon-cards (Article 7, Article 23, Article 7+23)
- Add quiz visual chrome improvements: progress bar, "Question X of Y" counter, full-width answer rows (CSS + minor JS changes in plugin stylesheet — logic unchanged)
- Set up `/quiz/` redirect per OD-3

**Join page (1 day)**
- Update `page-templates/page-join.php`
- New page title: "Join & Support"
- Two-track structure at top: Membership · Volunteer (Donate track held — OD-7)
  - Membership track card: scroll-anchors down to existing Individual/Family/Student tier cards
  - Volunteer track card: "Get Involved" → `/contact/`
- Existing tier cards stay on same page below the track cards (OD-2)
- Replace "What's included" with "Why Join TCLAS?" benefits grid
- "Contact Us →" link at bottom
- Note: Donate track card added in a future sprint once payment processor chosen

**MSP+LUX page (1.5 days)**
- Create `page-templates/page-msp-lux.php`, assign to `/msp-lux/` page
- Sections:
  1. Centered title + subtitle header
  2. 3 pillar cards: Business & Trade · Education & Research · Cultural Heritage (icon + heading + body each)
  3. "Connecting MSP & LUX" info section: 3 stat chips (flight time, distance, time difference), body text, external link
  4. "Helpful Resources" link groups: Official Luxembourg Resources + Minnesota Resources

**Ancestry page (1 day)**
- Create `page-templates/page-ancestry.php`, assign to `/ancestry/` page
- Sections:
  1. Title + subtitle header
  2. "Where Do I Start?" dark-bg card: 3 numbered steps, photo right, two CTA buttons
  3. Live read-only Leaflet map embed — `[tclas_ancestor_map]` shortcode, no auth required (OD-1). Inline prompt: "Join to see who shares your roots."
  4. Success stories: 3-card testimonials pulled from shared `testimonials` ACF repeater (OD-4)
  5. Research Resources link group (hardcoded external links)
  6. Membership CTA

### User tasks this week
- Provide: Article 7/23/7+23 pathway descriptions for Citizenship page
- Provide: MSP+LUX copy (pillar descriptions, connection info body text, resource URLs)
- Provide: Ancestry step copy, research resource links
- Provide: Volunteer description for Join page

---

## → March 22: Board Preview

**What the board sees:**
- Fully redesigned homepage
- Working nav: all 7 items (About · Events · Newsletter · MSP+LUX · Ancestry · Citizenship · Join Us) + Member Log In
- Citizenship, Join, MSP+LUX, Ancestry pages live
- Updated footer with all columns and social icons
- Events and Newsletter in current (functional) state — note to board that visual overhaul comes before launch

**What can be shown as "in progress":**
- Events archive custom template
- Newsletter sub-nav and Browse by Topic
- Stub content pages (FAQ, Mission, History, Contact, Financials) — content pending

---

## Phase 4 — Events + Newsletter (Mar 23 – Apr 4)

The two most complex remaining pieces.

### Events (4 days)

**Custom TEC archive template**
- Create `tribe-events/archive-tribe_events.php` (or wrapper page template if using `[tribe_events]` shortcode approach)
- Featured event section: query `_tclas_featured_event = 1`, render hero banner (image left, detail panel right, "FEATURED EVENT" badge)
- Upcoming events grid: pull future events, render 3-col card grid with MEMBERS ONLY badge overlaid on card image
- Past events grid: new `tclas_get_past_events()` helper in `inc/events-integration.php`, rendered as photo-card grid below upcoming

**New meta fields**
- `_tclas_featured_event` boolean meta box on TEC events (same pattern as `_tclas_members_only` in `inc/events-integration.php`)
- `_tclas_rsvp_count` number meta field for manual registrant count display

**Members Only badge**
- Update event card partial to position badge as image overlay (top-right corner) rather than below the title

### Newsletter (3 days)

**Sticky sub-nav component**
- New partial `template-parts/newsletter/sub-nav.php`
- "The Loon & The Lion" branding on left, tab links on right
- Tabs: Current Issue · Previous Issues · [one per visible `tclas_department` term]
- Sticky behavior via CSS `position: sticky` + JS scroll-shadow enhancement
- Active tab state tracked in JS

**Browse by Topic grid**
- 3×3 icon-card grid, one card per `tclas_department` term
- Each card: icon (from term meta `department_icon`), Luxembourgish term name, English translation
- Clicking a tab or card shows/hides article rows client-side (OD-6 — no page reload)

**Newsletter signup CTA box**
- Render `tclas_footer_newsletter_form()` inside a new styled dark-bg container variant at the bottom of the newsletter page

### User tasks (Phase 4)
- Set `_tclas_featured_event = 1` on an event in WP Admin to test featured banner
- Enter RSVP count on at least one event for testing
- Upload icons for all `tclas_department` terms in WP Admin (or confirm text/emoji approach as fallback)

---

## Phase 5 — Content + Polish (Apr 5–11)

### Dev tasks
- Stub pages: wire up any needed page templates for /contact/ (contact form), /faq/ (accordion or simple page), others as needed
- Cross-browser QA: Chrome, Firefox, Safari, Edge
- Mobile/tablet QA at 375px, 768px, 1024px breakpoints
- Accessibility pass: keyboard nav, focus styles, ARIA labels, color contrast on new sections
- Performance: ensure new hero images are properly sized (webp where possible, lazy-loaded), no layout shift
- 301 redirect `/quiz/` → `/citizenship/` confirmed working
- Check all footer links resolve to real pages

### User tasks (Phase 5) — **content is the critical path to launch**
- Write and enter content for: FAQ, Mission, History, Contact, Financials pages
- Final copy review on all pages built in Phases 2–4
- Confirm all testimonial photos have appropriate usage rights
- Confirm hero photo credits are correct and complete
- Confirm donate URL is live and correct

---

## Phase 6 — Launch Prep (Apr 12–17)

- Final content sign-off across all pages
- Confirm hosting/DNS plan (if moving from MAMP to production server)
- Production deployment checklist:
  - WP_DEBUG = false ✓ (already set)
  - SMTP mail configured (for quiz email submissions + PMPro receipts)
  - Adobe Fonts kit ID confirmed working on production domain
  - PMPro payment gateway live and tested
  - Mailchimp list IDs confirmed for production
  - All ACF Theme Options re-entered on production (not synced by default)
- Backup: full DB + files snapshot before go-live
- Staging smoke test: walk all 6 nav pages, join flow, quiz flow, login flow

---

## → April 18: Soft Launch

---

## Critical path

The items below, if delayed, will push the nearest deadline to the right.

| Blocker | Deadline | Owned by |
|---------|----------|----------|
| All 8 open decisions made | Mar 2 | You |
| Hero photos in WP Media Library | Mar 8 | You |
| Testimonials (3×: name, year, quote, photo) | Mar 8 | You |
| Homepage stats confirmed | Mar 8 | You |
| MSP+LUX copy + resource URLs | Mar 14 | You |
| Ancestry copy + resource links | Mar 14 | You |
| Article 7/23 pathway descriptions | Mar 14 | You |
| Department icons for Browse by Topic | Apr 3 | You |
| Stub page content (FAQ, Mission, etc.) | Apr 11 | You |
| Donate URL live | Apr 11 | You |
| Production hosting ready | Apr 12 | You |

---

## Effort summary

| Phase | Dev effort | Content/decisions |
|-------|-----------|------------------|
| 0 — Decisions | — | ~2 hrs your time |
| 1 — Foundation | ~2 days | Photo sourcing, testimonials, stats |
| 2 — Homepage | ~5 days | Hero photos, copy, ACF entries |
| 3 — Public pages | ~5 days | Page copy for 4 pages |
| 4 — Events + Newsletter | ~7 days | Featured event, icons |
| 5 — Content + Polish | ~4 days | All stub page content (critical) |
| 6 — Launch prep | ~2 days | Final sign-off, hosting |
| **Total dev** | **~25 days** | |

25 dev days across 7 weeks works if there are no major re-scopes after the board review.

---

## Post-launch backlog (not blocking launch)

These are improvements that didn't make the cut for April 18 but should be tracked:

- Ancestry page map teaser → upgrade to live Leaflet embed if OD-1 chose static
- Newsletter tab filtering → upgrade to client-side JS if OD-6 chose server-side initially
- Quiz chrome → further polish after user testing
- Events: integrate TEC RSVP/Tickets if manual count becomes unmanageable
- Homepage testimonials → rotate from ACF repeater (currently shows first 3 only)
- Performance: consider dropping Bootstrap CDN in favor of custom utility classes (current tech debt)
