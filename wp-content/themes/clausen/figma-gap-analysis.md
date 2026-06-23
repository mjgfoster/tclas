# Combined Site Spec — Figma + WP Build Reconciliation

**Figma prototype**: https://twist-statue-40827820.figma.site/
**Date reviewed**: 2026-02-28
**Scope**: Functional and structural reconciliation only. Visual/styling deltas not covered here.

---

## Approach

This document is not a pure Figma-to-WP migration. The Figma prototype is a design direction for the public-facing site — some pages are fully thought through, others are placeholder/incomplete. The WP build, meanwhile, has more developed functionality in several areas (notably Ancestry/genealogy, the quiz engine, and the member hub) that the Figma hasn't caught up to.

**The goal is a combined spec:** take the Figma's structure and new pages where it leads, keep and refine WP's more advanced features where it leads, and surface any real gaps between them.

**Which source leads per area:**

| Area | Leads |
|------|-------|
| Nav structure, page set | Figma |
| Homepage sections | Figma |
| Events layout | Figma |
| Newsletter structure | Figma |
| MSP+LUX page | Figma (new) |
| Ancestry page | **WP** — Figma is a rough placeholder; WP has the real features |
| Citizenship / Quiz | Figma layout, WP logic |
| Join page | Figma (expanded) |
| Footer | Figma |
| Member hub & all gated features | **WP** — not represented in Figma at all |
| Genealogy matching engine | **WP** |
| Citizenship quiz state machine | **WP** |

---

## How to read this

Each section describes what the Figma shows, what currently exists in WP, and what work is required. Complexity ratings are rough:

- **Low** — copy change, nav menu edit, ACF field add
- **Medium** — new page template or component, moderate PHP/JS work
- **High** — new plugin feature, custom TEC integration, multi-file changes

---

## 1. Site navigation

### Figma
Primary nav: **Events · Newsletter · MSP+LUX · Ancestry · Citizenship · Join Us**
Plus a search icon and a **"Member Log In"** text link — both in the header bar but visually separate from the main nav items.

### Current WP
Primary menu (WP Admin → Menus) is unspecified here, but `front-page.php` links to `/join/`, `/about/`, `/events/`, `/member-hub/`. The current primary menu likely includes About, which Figma drops.

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| N-1 | Update primary menu to: Events, Newsletter, MSP+LUX, Ancestry, Citizenship, Join Us | Low |
| N-2 | "Member Log In" moves out of the primary nav list into its own header element (separate from hamburger) — needs header.php + CSS | Medium |
| N-3 | Search icon in header — needs to wire up WP search or a custom search component | Low–Medium |

---

## 2. Homepage (`/`)

### Figma sections (in order)
1. **Split dual-photo hero** — Minneapolis photo left, Luxembourg City photo right, split down the center. Large italic serif title, orange rule, tagline, two CTAs ("Become a Member" + "Explore Events"), photographer credit overlays in each corner.
2. **Mission statement** — Centered text: "More than a heritage society, we are a bridge to modern Europe." + one supporting paragraph.
3. **Upcoming Events** — Eyebrow "HAPPENING SOON", h2, "View All Events →" link, 3-event card grid with MEMBERS ONLY badges on relevant cards.
4. **Newsletter preview** — Eyebrow with issue name/date, large "The Loon & the Lion" title, article TOC list (with Luxembourgish section labels) on left, large photo on right, "Browse the newsletter archives →" link.
5. **Testimonials + Stats** — h2 "Join 300+ Members Celebrating Luxembourg Heritage", 3 member testimonial cards (avatar photo + name + year + quote), then a 4-stat strip (300+ Active Members, 12+ Annual Events, 40 Years of Heritage, 150+ Dual Citizens Helped).
6. **Citizenship quiz CTA** — Dark teal/ardoise band: "You may already be a Luxembourg citizen." heading, 2-line body text, "Check Your Eligibility" pill button → links to /citizenship/.
7. **"Join the Community" CTA bar** — Orange/gold background, "Join the Community" heading, sub-text, two buttons: "Become a Member" + "Member Log In".

### Current WP (`front-page.php`)
1. Hero (ardoise): single illustration/ACF image, different title copy, CTAs vary by auth state.
2. Events strip (white): 3-card grid — same concept, different eyebrow.
3. Welcome/About (warm light): text column + illustration — "A community in two places at once."
4. News (off-white): 3 latest blog posts.
5. Membership tiers (ardoise): full 3-tier pricing cards.
6. Join CTA (crimson): short "Find your people." strip.

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| HP-1 | **Hero overhaul**: split dual-photo layout replaces single illustration. Needs `<picture>` or CSS background-image approach with two separate ACF image fields (one for each city). New ACF fields needed: `hero_photo_msp`, `hero_photo_lux`, `hero_photo_msp_credit`, `hero_photo_lux_credit`. Current `hero_illustration` / `hero_illustration_mobile` fields become unused. | High |
| HP-2 | Hero copy changes: new title, new tagline, CTAs no longer conditional on auth state (Figma shows same CTAs always). Consider whether the auth-conditional version is desirable to keep. | Low |
| HP-3 | **New section: Mission statement** (currently absent). Static copy block. | Low |
| HP-4 | Upcoming Events section: mostly compatible. Eyebrow copy changes ("HAPPENING SOON"). MEMBERS ONLY badge needs to be overlaid on card image (see Events §4). | Low |
| HP-5 | **New section: Newsletter preview**. Needs to query latest newsletter issue (posts with `tclas_issue_date` meta), pull the `tclas_department` taxonomy terms as TOC rows, display a featured image. This replaces the generic "Latest news & stories" section. | Medium |
| HP-6 | **New section: Testimonials**. Three hardcoded or ACF-managed testimonials (photo, name, member-since year, quote). Recommend ACF repeater field: `homepage_testimonials` on options page. | Medium |
| HP-7 | **New section: Stats strip**. Four stat values (300+, 12+, 40, 150+). ACF option fields or hardcoded. | Low |
| HP-8 | **New section: Citizenship quiz CTA** (dark band). Replaces current crimson Join CTA. Static copy + link to /citizenship/. | Low |
| HP-9 | **New section: "Join the Community" CTA bar** (orange background). Two buttons, no membership-gate logic. Replaces current crimson join-cta section. | Low |
| HP-10 | **Remove**: Membership tier cards section from homepage. Tiers now live only on /join/. | Low |
| HP-11 | **Remove**: Welcome/About illustration section. Replaced by mission statement. | Low |

---

## 3. Events page (`/events/`)

### Figma
- Page title header (light background, centered — no dark ardoise header).
- **Featured event** banner: large image left + detail panel right with "FEATURED EVENT" badge (orange), "COMING UP" pill badge, title, description, date/time/location, registrant count ("120 registered"), "Register Now" CTA.
- **Upcoming Events** 3-column grid — cards with image (MEMBERS ONLY badge overlaid top-right on image), title, excerpt, date, time, location, "View details →".
- **Past Events** 3-column grid — photo cards (title only).

### Current WP
The Events Calendar provides the archive. `inc/events-integration.php` registers `_tclas_members_only` and provides `tclas_get_upcoming_events()` / `tclas_render_event_card()`. No custom TEC archive template exists. There is no featured event mechanism and no past events section.

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| EV-1 | **Custom TEC archive template**. TEC looks for `tribe-events/` templates. Need `tribe-events/archive.php` (or the equivalent tribe template hierarchy) to replace the default TEC output with the Figma layout. | High |
| EV-2 | **"Featured Event" meta**. New `_tclas_featured_event` boolean meta box on TEC events (same pattern as `_tclas_members_only`). Featured event is queried separately and rendered as the hero banner. | Medium |
| EV-3 | **Registrant count**. Figma shows "120 registered". Either: (a) add a manual `_tclas_rsvp_count` meta field editors update themselves, or (b) integrate TEC's RSVP/Tickets add-on if available. Recommending (a) for simplicity. | Low–Medium |
| EV-4 | **Past Events section**. Query TEC events with end date in the past, render in grid. `tclas_get_upcoming_events()` already exists; need a companion `tclas_get_past_events()` helper in `inc/events-integration.php`. | Medium |
| EV-5 | **MEMBERS ONLY badge position**. Currently unknown placement; Figma places it as an overlay chip in the top-right of the card image. CSS change + markup tweak in the event card partial. | Low |
| EV-6 | Page header: Figma uses a light/centered style (no dark ardoise header band). If the current `tclas-page-header--ardoise` pattern is used for Events, it needs to be overridden or a new variant added. | Low |

---

## 4. Newsletter page (`/newsletter/`)

### Figma
- **Sticky horizontal sub-nav** below the main header: "The Loon & The Lion" branding on the left, then tab links: Current Issue · Previous Issues · Community · In the Kitchen · History · In Luxembourg.
- **Current issue view**: breadcrumb, issue date + name ("TCLAS NEWSLETTER · FEBRUARY / MARCH 2026"), large issue title, two-column layout (article TOC list left, large photo right). Each TOC item shows Luxembourgish section label + English label + article title + excerpt.
- **Browse by Topic**: 3×3 grid of icon-cards, one per `tclas_department` taxonomy term — each card has a circular icon, Luxembourgish name, and English translation.
- **Newsletter signup CTA**: dark background box at bottom, email input, Subscribe button, "Or browse past issues →" link.

### Current WP
`page-templates/page-newsletter.php` exists. The `tclas_department` taxonomy is registered. No sub-nav component exists. The Browse by Topic concept is not implemented.

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| NL-1 | **Sticky sub-nav component**. New PHP partial (e.g., `template-parts/newsletter/sub-nav.php`) injected via `wp_body_open` or directly in `page-newsletter.php`. Tabs link to the newsletter page filtered by `tclas_department` term or anchor. Needs JS for sticky behavior and active-tab state. | Medium |
| NL-2 | **"Current issue" two-column layout**. Query the most recent newsletter post (by `tclas_issue_date` meta), list its department-grouped articles as the TOC, display featured image on right. Logic to group and sort articles within an issue by `tclas_issue_order` meta. | Medium |
| NL-3 | **Browse by Topic icon grid**. Each `tclas_department` term needs an icon assigned to it. Options: (a) hardcode icon-per-term-slug in the template, or (b) add a term-meta field for icon (SVG/image). Grid renders all terms with Luxembourgish + English labels. | Medium |
| NL-4 | **Newsletter signup CTA box**. Render the MC4WP form (from `footer_mc4wp_form_id` ACF option) inside a styled dark-background container at the bottom of the page. Function `tclas_footer_newsletter_form()` already exists; needs a new wrapper/variant for this in-page context. | Low |
| NL-5 | Sub-nav tab filtering: clicking a tab (e.g., "In the Kitchen") should filter the article list to that department. Decide: server-side (reload with `?department=term-slug`) or client-side JS filtering. | Medium |

---

## 5. MSP+LUX page (`/msp-lux/`) — **New page**

### Figma
- Title: "MSP + LUX Connections", subtitle about Twin Cities–Luxembourg ties.
- Three pillar cards: Business & Trade · Education & Research · Cultural Heritage (icon + title + description each).
- "Connecting MSP & LUX" section: flight time, distance, time difference stats, body text, "Visit Luxembourg Airport website" external link.
- "Helpful Resources" section: grouped link lists — Official Luxembourg Resources (gov portal, ministry, Visit Luxembourg, Luxembourg USA) + Minnesota Resources (Meet Minneapolis + others).

### Current WP
**Does not exist.**

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| MSP-1 | **Create WP page** with slug `msp-lux`, assign a page template or use a full-width default template with the content below. | Low |
| MSP-2 | **Page template** `page-templates/page-msp-lux.php`. Static sections: pillar cards, connection info, resources. Content could be hardcoded or ACF-managed. Recommend hardcoded for now (single static page). | Medium |
| MSP-3 | Add to primary nav as "MSP+LUX". | Low |

---

## 6. Ancestry page (`/ancestry/`) — **WP leads; Figma is placeholder**

### Figma
Shows a rough "Trace Your Roots" concept page with a 3-step guide, testimonials, and external research links. This page was **not fully designed** — it is an early placeholder and does not reflect the actual ancestry functionality already built.

### Current WP (authoritative)
The WP build has several mature ancestry features that the Figma does not cover:

- **`[tclas_ancestor_map]`** shortcode (`/map/`, page ID 72): Leaflet.js interactive map of Luxembourg communes, circle-radius-scaled by member count, privacy-filtered by `_tclas_visibility`.
- **My Story form** (`/member-hub/my-story/`): Members enter ancestral communes and surnames; normalized + stored in user meta.
- **"How Are We Connected" engine** (`inc/connections.php`): fuzzy genealogy matching against all other members, connection scoring (0–10), caching, nightly cron.
- **Member profiles** with commune/surname data surfaced.
- **534 commune dataset** with LOD.lu audio pronunciation.
- **Admin: Unresolved Genealogy Queue** for entries that fail fuzzy matching.

The Figma's guide steps ("Gather What You Know / Search U.S. Records / Connect to Luxembourg") and research resources links are a useful public-facing intro layer, but the deeper functionality already exists in the member hub.

### Combined target: a two-tier Ancestry section

The goal is a **public-facing `/ancestry/` page** that introduces the ancestry features and drives sign-ups, plus the **existing members-only features** intact in `/member-hub/`.

**Public `/ancestry/` page (new):**
- Hero/intro: "Trace Your Roots" — what TCLAS can help you do
- 3-step beginner guide (from Figma: Gather / Search / Connect)
- "Explore the Ancestral Commune Map" teaser — show a static preview image or a read-only version of the map as a hook; full interactive map requires membership
- Success stories (3 testimonials)
- Research resources (external links)
- CTA: "Become a Member to connect with others who share your roots"

**Members-only features (already built, no changes needed):**
- `/map/` with full interactive Leaflet map
- `/member-hub/my-story/` form
- "How Are We Connected" panel in member hub dashboard

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| AN-1 | **Create WP page** with slug `ancestry`, assign template, add to primary nav as "Ancestry". | Low |
| AN-2 | **Page template** `page-templates/page-ancestry.php`. Sections: intro, guide steps (dark bg card with numbered list + photo), commune map teaser (static image or embed with gate overlay), success stories, research resources, membership CTA. | Medium |
| AN-3 | **Map teaser**: options are (a) a static screenshot/illustration of the map with "Unlock the full map — become a member" overlay, or (b) a read-only Leaflet embed (already works anonymously — the map only shows aggregate data, no PII, so this is safe to show publicly). Option (b) is low-effort and more compelling. | Low |
| AN-4 | Success stories: reuse the same ACF repeater from homepage testimonials (HP-6) or create a separate `ancestry_testimonials` repeater. | Low |
| AN-5 | Research resources content (URLs, descriptions) needs to be authored and maintained. Recommend hardcoding in the template initially. User task for content. | — |
| AN-6 | `/map/` URL: stays as-is for member use. Optionally redirect it to `/ancestry/` for non-members and let them deep-link after login. | Low |

---

## 7. Citizenship page (`/citizenship/`) — **Restructured**

### Figma
- Title: "Citizenship Quiz", subtitle.
- **Embedded quiz** at top of page: different UI from current plugin — progress bar, "Question X of 3" counter, large single-question display, full-width clickable answer rows, legal disclaimer below.
- "Pathways to Citizenship" section: 3 cards — Article 7 · Article 23 · Article 7+23 (icon + title + description each).

### Current WP
The quiz lives at `/quiz/` (page ID 69) via `[luxembourg_eligibility_quiz]` shortcode, which is the custom plugin v1.4 with its own CSS/JS. The Citizenship page may already exist as a WP page but likely has no content. The "Pathways" section does not exist.

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| CZ-1 | **Create/update `/citizenship/` page** with `[luxembourg_eligibility_quiz]` shortcode embedded, and add "Pathways to Citizenship" static content below. | Low |
| CZ-2 | **Quiz UI reskin**. The Figma shows a simplified 3-question prototype; the real plugin v1.4 has a multi-generational state machine with many more states. The visual chrome (progress bar, full-width answer rows, "Question X of Y" counter) should be rebuilt in the plugin's CSS — the underlying JS logic stays the same. | Medium |
| CZ-3 | **Redirect `/quiz/` → `/citizenship/`**. Once the shortcode moves, add a WP redirect from the old quiz URL to the citizenship page. Can be done via Redirection plugin, `.htaccess`, or `wp_redirect` in a `template_redirect` hook. | Low |
| CZ-4 | "Pathways to Citizenship" static section (Article 7 / 23 / 7+23 cards). Hardcoded in template or ACF-managed. | Low |
| CZ-5 | Citizenship page → primary nav as "Citizenship". | Low |

---

## 8. Join page (`/join/`) — **Extended**

### Figma
- Title: "Join & Support"
- Three equal-weight track cards: **Membership** (icon, description, bullet list, "Become a Member" CTA → PMPro) · **Donate** (icon, description, bullets, "Make a Donation" CTA → external) · **Volunteer** (icon, description, bullets, "Get Involved" CTA → contact/form)
- "Why Join TCLAS?" benefits grid (4 items: Cultural Connection, Community Network, Educational Programs, Dual Citizenship Support).
- "Contact Us →" link at bottom.

### Current WP (`page-templates/page-join.php`)
Membership tier cards (Individual/Family/Student) with PMPro checkout links. A "What's included" features section. A final CTA. No Donate or Volunteer sections.

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| JN-1 | **Rename page title** to "Join & Support". Update `<h1>` in `page-join.php`. | Low |
| JN-2 | **Add Donate track**. A new card with benefits list and CTA. Needs a destination: either an external donation processor URL or a future `/donate/` page. For now, can point to a placeholder or `org_email`. Add ACF option `donation_url` (URL field) to Theme Options. | Low–Medium |
| JN-3 | **Add Volunteer track**. New card with benefits list and "Get Involved" CTA. Destination: probably a contact form page or `org_email` mailto. | Low |
| JN-4 | The 3-tier pricing cards (Individual/Family/Student) currently live here. In Figma the join page shows Membership as a single track, not three tiers upfront. Decide: keep the tier detail on this page (scroll below) or move to a separate `/membership/` sub-page. The PMPro checkout still needs `?level=X`. | Medium |
| JN-5 | **"Why Join TCLAS?" benefits section** replaces current "What's included" section. Copy + layout update. | Low |
| JN-6 | "Contact Us →" link at bottom. Create `/contact/` page first (see §10), then wire up. | Low |

---

## 9. Footer — **Updated**

### Figma
- Logo + "Twin Cities Luxembourg American Society" wordmark + tagline.
- Social icons: **Facebook · Instagram · LinkedIn** (3 icons).
- "Explore" column: Newsletter, Events, Ancestry, Citizenship, FAQ, Join Us, Member Log In.
- "About" column: Mission, History, Board of Directors, Contact Us, Financials.
- "Stay in Touch": email input + Subscribe button (MC4WP).

### Current WP
Footer has Facebook only. `footer-main` and `footer-org` nav menus. MC4WP form via `tclas_footer_newsletter_form()`.

### Gaps
| # | Gap | Complexity |
|---|-----|-----------|
| FT-1 | **Add Instagram + LinkedIn URL fields** to ACF Theme Options (`instagram_url`, `linkedin_url`). Update footer.php to render all three icons conditionally. | Low |
| FT-2 | **Footer nav menus**: Update `footer-main` (→ "Explore") and `footer-org` (→ "About") in WP Admin → Menus to match Figma link lists. Several of these pages don't exist yet (FAQ, Mission, History, Financials — see §10). | Low (once pages exist) |
| FT-3 | Footer column heading labels change: "Explore" and "About" — update in `footer.php`. | Low |

---

## 10. New pages required

These are linked from the footer or elsewhere in the Figma but do not exist in the current WP build.

| Page | Slug | In nav? | Notes |
|------|------|---------|-------|
| FAQ | `/faq/` | Footer Explore | Static content. |
| Mission | `/mission/` or `/about/mission/` | Footer About | Static content. Could be a section of /about/. |
| History | `/history/` or `/about/history/` | Footer About | Static content. |
| Contact Us | `/contact/` | Footer About | Contact form (WPForms or similar, or WP's native contact block). |
| Financials | `/financials/` | Footer About | PDF link or static content. |

All are **Low complexity** to create (WP pages with default template + copy). Copy is a user task.

---

## 11. Existing pages with no Figma representation

These pages exist in the current WP build but have no equivalent in the Figma prototype:

| Page | Slug | Status |
|------|------|--------|
| Ancestral Map | `/map/` | Still functional; link from Ancestry page (AN-3). Not in main nav. |
| Quiz (standalone) | `/quiz/` | Redirect to /citizenship/ once CZ-1 is done. |
| Member Hub | `/member-hub/` | Not shown (Figma is public-facing only). Unchanged. |
| My Story | `/member-hub/my-story/` | Same as above. |
| Member Profiles | `/member-hub/profiles/` | Same. |
| Newsletter (old) | `/newsletter/` | Being replaced by the new design in §4. |
| Referral landing | `/welcome/` | Not shown; keep as-is. |

---

## 12. Summary by area

| Area | New pages | New templates | New ACF fields | New WP features | Lead source | Estimated work |
|------|:---------:|:-------------:|:--------------:|:---------------:|:-----------:|:--------------:|
| Navigation | — | — | — | Menu update | Figma | Small |
| Homepage | — | Sections replaced | `hero_photo_msp/lux` + credits, `homepage_testimonials`, stat fields | — | Figma | Large |
| Events | — | Custom TEC archive template | `_tclas_featured_event`, `_tclas_rsvp_count` | Past events helper | Figma | Large |
| Newsletter | — | Sub-nav component, topic grid | Term icon meta | JS tab filtering | Figma | Medium |
| MSP+LUX | ✓ | ✓ | — | — | Figma | Small–Medium |
| Ancestry | ✓ | ✓ | — | Map teaser option | **WP** | Medium |
| Citizenship | — | Restructured | — | Quiz redirect | Both | Small |
| Join | — | Extended | `donation_url` | — | Figma | Small |
| Footer | — | Updated | `instagram_url`, `linkedin_url` | — | Figma | Small |
| New stub pages (FAQ, etc.) | ✓ ×5 | Default | — | — | Figma | Small (content = user task) |

---

## 13. WP features with no Figma counterpart — keep as-is

The Figma is public-facing only. The following are either gated or background systems not represented in the prototype. All should remain unchanged:

**Gated member features:**
- Member hub dashboard (`/member-hub/`) and all sub-pages
- "How Are We Connected" genealogy matching engine (`inc/connections.php`)
- My Story form and commune/surname data storage
- Member profiles and directory (`/member-hub/profiles/`)
- Member documents and resources pages
- Referral system and referral landing page (`/welcome/`)

**Background systems:**
- PMPro checkout flow, membership levels, expiry warnings
- Mailchimp auto-subscribe / unsubscribe hooks
- Ltz tagger and tooltip system
- LOD.lu audio pronunciation API
- bbPress forum (Luxembourg Connections)
- National Day season detection
- All AJAX handlers
- WP-Cron connection recompute job
- Admin: Unresolved Genealogy Queue

**Quiz:**
- The citizenship quiz state machine logic (plugin v1.4) is unchanged
- Only the visual chrome changes (see CZ-2)

**Events:**
- TEC event data, settings, individual event pages unchanged
- Only the archive/listing template changes (see §3)

---

## 14. Decisions log

All 8 decisions resolved 2026-03-01.

| ID | Decision | Outcome | Implementation note |
|----|----------|---------|---------------------|
| OD-1 | Ancestry map on public `/ancestry/` | **Live read-only Leaflet embed** | Embed the existing `[tclas_ancestor_map]` shortcode without auth check. Safe — aggregate data only. Prompts sign-up inline. See AN-3. |
| OD-2 | Membership tier cards on `/join/` | **Same page, scroll anchor** | "Become a Member" track card scrolls down to existing tier cards section on `/join/`. No new sub-page. See JN-4. |
| OD-3 | `/quiz/` URL after move | **301 redirect to `/citizenship/`** | Configure redirect in `.htaccess` or via `template_redirect` hook. See CZ-3. |
| OD-4 | Testimonials | **One shared ACF repeater** | Single `testimonials` repeater in Theme Options. Homepage shows rows 1–3; ancestry page shows all (or a configurable offset). See HP-6, AN-4. |
| OD-5 | Homepage stats | **ACF option fields** | `homepage_stats` repeater (label + value pairs) in Theme Options. Editable in WP Admin without code changes. See HP-7. |
| OD-6 | Newsletter tab filtering | **Client-side JS** | Clicking a tab shows/hides article rows in the DOM. No page reload. See NL-5. |
| OD-7 | Donate track on `/join/` | **Hold entirely** | Do not build the Donate card yet. Ship `/join/` with Membership + Volunteer tracks only. Add Donate when a payment processor is chosen. `donation_url` ACF field not needed yet. |
| OD-8 | `/about/` page | **Keep in primary nav** | Nav is 7 items: About · Events · Newsletter · MSP+LUX · Ancestry · Citizenship · Join Us. Figma's 6-item nav overridden. Order TBD. |

---

*Document updated 2026-03-01. Based on direct review of https://twist-statue-40827820.figma.site/, reading of `front-page.php`, `page-templates/page-join.php`, and the functionality spec (`site-functionality-spec.md`).*
