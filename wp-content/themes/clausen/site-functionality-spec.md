# TCLAS Site — Functionality Specification

**Site**: Twin Cities Luxembourg American Society (TCLAS)
**Theme**: Clausen v1.2 ("Ciel Bleu")
**WordPress root**: `/Applications/MAMP/htdocs/tclas-local/`
**Local URL**: `http://localhost:8888/tclas-local/`
**Document scope**: Functionality only — plugins, settings, integrations, custom code behavior. Not visual/styling.

---

## Table of Contents

1. [Plugins](#1-plugins)
2. [Custom Post Types & Taxonomies](#2-custom-post-types--taxonomies)
3. [ACF Field Groups & Theme Options](#3-acf-field-groups--theme-options)
4. [Paid Memberships Pro](#4-paid-memberships-pro)
5. [Shortcodes](#5-shortcodes)
6. [Luxembourg Citizenship Quiz (Custom Plugin)](#6-luxembourg-citizenship-quiz-custom-plugin)
7. [Ancestral Commune Map](#7-ancestral-commune-map)
8. ["How Are We Connected" — Genealogy Matching](#8-how-are-we-connected--genealogy-matching)
9. [Member Profiles & Directory](#9-member-profiles--directory)
10. [Referral System](#10-referral-system)
11. [The Events Calendar Integration](#11-the-events-calendar-integration)
12. [Mailchimp for WP Integration](#12-mailchimp-for-wp-integration)
13. [Member Hub](#13-member-hub)
14. [Luxembourgish Language Tagger](#14-luxembourgish-language-tagger)
15. [LOD.lu Audio Pronunciation](#15-lodlu-audio-pronunciation)
16. [National Day Season Detection](#16-national-day-season-detection)
17. [AJAX Handlers](#17-ajax-handlers)
18. [JavaScript — Front-End Behavior](#18-javascript--front-end-behavior)
19. [Asset Enqueuing & Localized Data](#19-asset-enqueuing--localized-data)
20. [Navigation Menus & Widget Areas](#20-navigation-menus--widget-areas)
21. [Image Sizes](#21-image-sizes)
22. [Page Templates](#22-page-templates)
23. [wp-options & WP-Cron](#23-wp-options--wp-cron)
24. [Security & Nonces](#24-security--nonces)
25. [Admin Extensions](#25-admin-extensions)

---

## 1. Plugins

### Active plugins

| Plugin | Version | Role |
|--------|---------|------|
| Paid Memberships Pro | 3.6.5 | Membership levels, checkout, subscriptions |
| Advanced Custom Fields Pro | 6.7.1 | Field groups, options pages |
| Brevo | 3.3.2 | Email marketing (slug: `mailin`) |
| The Events Calendar | 6.15.17 | Event management and calendar |
| bbPress | — | Forums (Luxembourg Connections) |
| Akismet | — | Comment spam protection |
| open-user-map | — | User location mapping |
| pmpro-update-manager | — | PMPro update management |
| WP Recipe Maker | 10.4.0 | Recipe CPT with JSON-LD schema |
| Modern Footnotes | 1.4.20 | Inline footnotes via `[mfn]...[/mfn]` |
| The SEO Framework | — | OG/Twitter Card meta, meta descriptions |
| Disable Comments | 2.6.2 | Disables comment system sitewide |
| **luxembourg-citizenship-quiz** | **2.0** | **Custom plugin — citizenship eligibility quiz** |

### Custom plugin — luxembourg-citizenship-quiz

- **Location**: `wp-content/plugins/luxembourg-citizenship-quiz/`
- **Shortcode**: `[luxembourg_eligibility_quiz]`
- Own CSS and JS enqueued by plugin
- AJAX email handler (`lcq_send_results`)
- See [§6](#6-luxembourg-citizenship-quiz-custom-plugin) for full detail

---

## 2. Custom Post Types & Taxonomies

### Post types

#### `tclas_story` — Luxembourg Stories
- **Archive slug**: `/stories/`
- **Supports**: title, editor, author, thumbnail, excerpt
- **REST API**: enabled
- **ACF fields**:
  - `story_member_name` (text)
  - `story_connection_type` (checkbox: ancestry, citizenship, marriage, work, travel, culture)
  - `story_immigration_generation` (select: 1st–4th, further, N/A)
  - `story_citizenship_status` (select: citizen, in progress, eligible, researching, N/A)

#### `tclas_board` — Board Members
- **Public archive**: no
- **Supports**: title, thumbnail, page-attributes (for manual ordering)
- **ACF fields**:
  - `board_role` (text)
  - `board_bio` (textarea)
  - `board_email` (email)

### Taxonomies

| Taxonomy | Attached to | Hierarchical | Notes |
|----------|-------------|:---:|-------|
| `tclas_commune` | tclas_story, post | No | 583 Luxembourg places from official index (localities + municipalities) |
| `tclas_surname` | tclas_story, post | No | Family surnames |
| `tclas_generation` | tclas_story | Yes | Immigration generation (1st, 2nd, …) |
| `tclas_department` | post | No | Newsletter sections (intro, main-story, community, recipe, news) |
| `tclas_category` | post | Yes | Content categories (public-facing) |

#### Commune term meta (ACF)
- `tclas_commune_wikipedia_url` (URL) — 484/583 populated
- `tclas_commune_lux_website_url` (URL) — 481/583 populated

#### Commune term meta (custom)
- `tclas_municipality` (text) — parent municipality name (e.g., "Käerjeng" for Bascharage)
- `tclas_commune_lux_name` (text) — Luxembourgish place name
- `tclas_commune_lat` (float) — latitude
- `tclas_commune_lng` (float) — longitude

#### Commune data architecture
Two data sources exist and must be kept in sync:
1. **`inc/commune-data.php`** — hardcoded PHP array (583 entries) returned by `tclas_get_communes()`. Contains: name, lux (Luxembourgish name), municipality, canton, lat, lng. Used by templates for display.
2. **WordPress term meta** — ACF fields (wikipedia_url, lux_website_url) + custom meta (municipality, lux_name, lat, lng). Used for enrichment data on commune profile pages.

---

## 3. ACF Field Groups & Theme Options

**Theme Options** are registered as an ACF Options page.

### Theme Options — General (`group_tclas_options`)

| Field key | Type | Default | Purpose |
|-----------|------|---------|---------|
| `adobe_fonts_kit_id` | text | pck6hdf | Typekit kit ID for Adobe Fonts |
| `footer_newsletter_form_id` | number | — | Brevo form ID displayed in footer |
| `referral_base_url` | URL | — | Landing page for referral links |
| `brevo_members_list_id` | text | — | Brevo list ID for member sync |
| `facebook_group_url` | URL | https://www.facebook.com/groups/tclas | Facebook community group link |
| `national_day_mode` | true/false | false | Manual override for National Day season |
| `org_address` | textarea | — | Organization address (footer) |
| `org_email` | email | — | Contact email address |
| `price_individual` | number | 30 | Individual membership price (display only) |
| `price_family` | number | 45 | Family membership price (display only) |
| `price_student` | number | 15 | Student membership price (display only) |

> Note: price fields are display-only. Actual billing prices are set in PMPro.

### Theme Options — Hero Photos & Branding

| Field key | Type | Purpose |
|-----------|------|---------|
| `hero_photo_msp` | image | Homepage hero — Minneapolis photo |
| `hero_photo_msp_credit` | text | Photo credit overlay |
| `hero_photo_lux` | image | Homepage hero — Luxembourg photo |
| `hero_photo_lux_credit` | text | Photo credit overlay |
| `footer_logo` | image | Light logo variant for dark footer (currently unused — hardcoded SVG) |
| `mapbox_access_token` | text | Mapbox API token for ancestral map |
| `mapbox_style_url` | text | Mapbox style URL for ancestral map |

### Theme Options — Content Repeaters

| Field key | Type | Purpose |
|-----------|------|---------|
| `homepage_stats` | repeater | Stat values (rows of `stat_value` + `stat_label`) |
| `testimonials` | repeater | Member testimonials (`name`, `member_since`, `location`, `photo`, `quote`) |

### Newsletter / Loon & Lion Post Fields

Applied to `post` post type (newsletter issues):
- `tclas_issue_date` (text, YYYY-MM) — Issue month
- `tclas_issue_order` (number) — Ordering within issue TOC

---

## 4. Paid Memberships Pro

**Version**: 3.6.5

### Membership levels

Levels are configured in PMPro admin. The theme references three tiers by their level ID:

| Level ID | Tier | Price (from ACF display) |
|----------|------|---------|
| 1 | Individual | $30/yr |
| 2 | Family | $45/yr |
| 3 | Student | $15/yr |

### Theme integration points

#### `pmpro_after_checkout`
- Credits referral source if `tclas_referral` cookie present (see [§10](#10-referral-system))
- Member ↔ Brevo sync handled by FuseWP (when installed)

#### `pmpro_after_change_membership_level`
- Brevo list removal handled by FuseWP (when installed)

#### `login_redirect`
- Authenticated members are redirected to `/member-hub/` instead of `/wp-admin/`

### Helper functions

| Function | Returns | Notes |
|----------|---------|-------|
| `tclas_is_member()` | bool | True if current user has active PMPro level |
| `tclas_membership_status()` | string | `'none'`, `'active'`, `'expiring'`, `'expired'` |
| `tclas_days_to_expiry()` | int | Days until membership expires |

### Expiry warnings
- Auto-displayed when membership expires within 30 days or is already expired
- Dismissible via session storage (JS)

---

## 5. Shortcodes

| Shortcode | Source | Page used | Function |
|-----------|--------|-----------|----------|
| `[luxembourg_eligibility_quiz]` | Custom plugin | /citizenship/ | Citizenship eligibility quiz v2.0 (see §6) |
| `[tclas_ancestor_map]` | Theme — `inc/ancestor-map.php` | /member-hub/ancestral-map/ | Interactive Leaflet map (members, split layout) |
| `[tclas_ancestor_map public="true"]` | Theme — `inc/ancestor-map.php` | /ancestry/ | Read-only public map embed |
| `[ltz t="translation"]...[/ltz]` | Theme — `inc/template-functions.php` | Post content | Luxembourgish tooltip (wraps `tclas_ltz()`) |
| `[wprm-recipe id=""]` | WP Recipe Maker | Post content | Recipe card with JSON-LD schema |
| `[mfn]...[/mfn]` | Modern Footnotes | Post content | Inline footnotes |

---

## 6. Luxembourg Citizenship Quiz (Custom Plugin)

**Plugin**: `wp-content/plugins/luxembourg-citizenship-quiz/`
**Version**: 2.0
**Shortcode**: `[luxembourg_eligibility_quiz]`

### What it does
A client-side JavaScript state machine that guides the user through a multi-generational eligibility assessment for Luxembourg citizenship. v2.0 features lineage tracing with a real-time family tree sidebar, smart 1969-skip logic, and visual lineage tracker.

### Design decisions
- **No dates collected** — uses smart 1969-skip logic (if parent was born before 1969, grandparent must have been too)
- Up to **5 ancestor generations** can be traced
- **Lineage tracker sidebar** — real-time family tree visualization showing the path being traced
- Pure client-side logic; no data persisted server-side except via the optional email result submission

### States

| State | Purpose |
|-------|---------|
| `start` | Initial prompt |
| `adopted_check` | Handles adoption (unique legal pathway) |
| `generation_loop` | Iterates through ancestor generations with lineage tracking |
| `living_check` | Determines if connecting ancestor is living (affects Article 23 pathway) |

For each generation in the loop, the quiz collects:
- Birth year category (before/after 1969) — auto-skipped when logically determined
- Gender (affects pre-1969 transmission rules)
- Birth country (was ancestor born in Luxembourg?)

### Outcomes

| Outcome key | Description |
|-------------|-------------|
| `outcome_article7` | Direct descent — unbroken line to Luxembourg ancestor |
| `outcome_article23_living` | Living parent/grandparent must apply first |
| `outcome_article23_deceased` | Posthumous recognition pathway |
| `outcome_adopted` | Special case — manual legal review required |
| `outcome_too_deep` | 6+ generations — outside standard legal pathways |

### AJAX email handler

- **Action**: `lcq_send_results` (registered for both logged-in and anonymous users)
- **Validates**: nonce (`lcq_email_nonce`), email address format
- **Sends**: plain-text email to user with result summary
- **Response**: JSON `{success, message}`

---

## 7. Ancestral Commune Map

**Location**: `inc/ancestor-map.php`
**Shortcode**: `[tclas_ancestor_map]` (accepts `public="true"` for unauthenticated embed)
**Pages**: /member-hub/ancestral-map/ (page ID 72, full member map), /ancestry/ (public embed)

### What it does
An interactive Leaflet.js map of Luxembourg showing circles at ancestral commune locations. Circle radius scales with the number of members who have that commune in their genealogy. Privacy-respecting: shows only aggregate data, never individual names. Split layout on member map: map + filtered member list sidebar.

### Data
- Commune coordinates: 583 Luxembourg places from official place-name index (`tclas_get_communes()` in `inc/commune-data.php`)
- Member ancestry: drawn from `_tclas_communes_norm` user meta
- Privacy filter: members with `_tclas_visibility = 'hidden'` are excluded from the map

### Map tiles
- **Primary**: Mapbox custom style via Static Tiles API (raster), style ID from ACF `mapbox_style_url`
- **Fallback**: CartoDB Positron (if Mapbox token not set)
- Markers: crimson `#8B3A3A` fill, white stroke
- Canton fills: 12 pastel tints at 0.75 opacity
- Fog mask: black at 0.1 opacity (dims neighboring countries)

### Libraries
- Leaflet 1.9.4 (CDN: cdnjs.cloudflare.com)
- Custom JS: `assets/js/tclas-ancestor-map.js`
- Custom CSS: `assets/css/tclas-ancestor-map.css`

### Commune profile pages
- Template: `taxonomy-tclas_commune.php`
- URL: `/member-hub/ancestral-map/commune/{slug}/`
- Three-column layout: facts (municipality, canton, coordinates, external links), LOD.lu pronunciation audio, mini-map
- Communes A-Z index: `page-templates/page-communes.php` at `/member-hub/ancestral-map/commune/`

---

## 8. "How Are We Connected" — Genealogy Matching

**Location**: `inc/connections.php`, `inc/connection-data.php`
**Access**: Members only

### What it does
Matches members by shared ancestral communes and surnames. Surfaces "connections" (other members you may be related to) in the member hub dashboard.

### Data flow

1. **Member saves story** at `/member-hub/my-story/` via AJAX form
2. **Normalization** (`tclas_save_member_story`):
   - Raw values stored for display
   - Normalized/canonical values stored for matching
3. **Immediate computation** (`tclas_compute_connections`):
   - Compares normalized values against all other members
   - Calculates connection scores (0–10)
   - Caches results in `_tclas_connections_cache`
4. **Nightly re-computation** (`tclas_connection_cron`):
   - Runs at 2 a.m. via WP-Cron
   - Processes all members in batches of 50

### Matching algorithm
- Levenshtein fuzzy matching with adaptive edit-distance threshold
- Diacritics stripped (à→a, ü→u, etc.)
- Case-insensitive, whitespace-normalized
- Surname variant clustering: e.g., "Schmitt" and "Smith" merged under one canonical cluster head (defined in `connection-data.php`)

### Connection strength

| Score | Label |
|-------|-------|
| 8–10 | Remarkable connection |
| 5–7 | Strong connection |
| 0–4 | Possible connection |

### User meta fields

| Meta key | Type | Purpose |
|----------|------|---------|
| `_tclas_communes_raw` | string[] | User's original input (display) |
| `_tclas_communes_norm` | string[] | Canonical commune slugs (matching) |
| `_tclas_surnames_raw` | string[] | User's original input (display) |
| `_tclas_surnames_norm` | string[] | Canonical cluster heads (matching) |
| `_tclas_visibility` | string | `'members'`, `'board'`, or `'hidden'` |
| `_tclas_open_to_contact` | int | 1 = show contact button on profile |
| `_tclas_profile_complete` | int | 1 once ≥1 commune or surname saved |
| `_tclas_connections_cache` | array | Computed connection objects |
| `_tclas_connections_computed_at` | int | Unix timestamp of last compute |

---

## 9. Member Profiles & Directory

**Location**: `inc/member-profiles.php`
**Page template**: `page-templates/page-member-profiles.php`
**Pages**: /member-hub/profiles/ (directory), /member-hub/profiles/{username}/ (individual)

### URL routing
- Custom rewrite rule: `^member-hub/profiles/([^/]+)/?$` → query var `tclas_profile_username`
- Profile URL: `/member-hub/profiles/{username}/`
- Directory URL: `/member-hub/profiles/`

### Profile features
- **Photo upload/remove**: AJAX; stored as WP attachment, ID in `_tclas_profile_photo`
- **Founding member badge**: auto-detected for 2026 cohort or admin-flagged
- **Visibility controls**: `_tclas_visibility` meta ('members', 'board', 'hidden')
- **Contact preference**: `_tclas_open_to_contact` meta (shows/hides contact button)
- **Member since**: queried from PMPro `pmpro_memberships_users` table (`startdate`)

---

## 10. Referral System

**Location**: `inc/referral.php`

### Mechanics

1. **Cookie capture** (`tclas_capture_referral_cookie` — `init` hook):
   - Reads `?ref={username}` from URL
   - Stores in `tclas_referral` cookie (30-day expiry, httponly, SameSite=Lax)

2. **Referral credit** (`tclas_credit_referral` — called from `pmpro_after_checkout`):
   - Increments referrer's `_tclas_referral_count` user meta
   - Sets `_tclas_referred_by` on newly registered user

3. **Referral URL generation** (`tclas_get_referral_url`):
   - Constructs `{base_url}?ref={username}`
   - Base URL from ACF option `referral_base_url` or falls back to `/welcome/`

4. **Landing page context** (`tclas_set_referral_context`):
   - Sets `$tclas_referrer` global for template use on `/welcome/` page

5. **Copy analytics** (`tclas_ajax_referral_copy`):
   - Increments `_tclas_referral_copy_count` when member copies link

### User meta

| Meta key | Purpose |
|----------|---------|
| `_tclas_referral_count` | Number of members this user has referred |
| `_tclas_referral_copy_count` | Number of times this user has copied their referral link |
| `_tclas_referred_by` | Username of referrer (set on new user) |

---

## 11. The Events Calendar Integration

**Location**: `inc/events-integration.php`

### Custom additions to TEC

- **Members-only meta box**: checkbox `_tclas_members_only` on TEC event edit screen (nonce-verified on save)
- **AP style date formatting**: Converts "@ pm" → "at p.m." across event output
- **Helper functions**:
  - `tclas_get_upcoming_events()` — returns next N events
  - `tclas_render_event_card()` — renders a standard event card partial

### Member hub integration
- Dashboard shows 2 next upcoming events
- Members-only events surface in hub; non-members see a gate prompt

---

## 12. Brevo Integration

**Location**: `inc/brevo-integration.php`
**Plugin**: Brevo (slug: `mailin`, v3.3.2)

### Footer newsletter form
- `tclas_footer_newsletter_form()` renders Brevo form using ID from ACF `footer_newsletter_form_id`
- Falls back to instructional text if no form ID is configured

### Member sync
- **FuseWP** (premium plugin, not yet installed) handles PMPro ↔ Brevo sync
- Subscribe on checkout, remove on cancellation

### Quiz email capture
- Quiz AJAX handler adds contacts to Brevo list (ID from `lcq_brevo_list_id` wp-option)
- Sets `QUIZ_COMPLETER` boolean attribute on the contact

### Dependencies
- Requires Brevo plugin active with API key entered
- Requires `footer_newsletter_form_id` set in ACF Theme Options for footer form
- Requires FuseWP for PMPro member sync (not yet installed)

---

## 13. Member Hub

**Location**: `inc/member-hub.php`, `page-templates/page-member-hub.php`
**Page**: /member-hub/ (ID 22)

### Access control
- Non-members (and logged-out users) are redirected to `/join/?tclas_gate=1`

### Dashboard sections
1. Upcoming events (2 next events via `tclas_get_upcoming_events()`)
2. Member directory link
3. Documents & resources link
4. Luxembourg Connections forum link (bbPress)
5. "How Are We Connected" connections panel
6. Referral card (share link, copy count display)

### Connections panel behavior
- Auto-marks connections as "seen" after 4 seconds (JS)
- Individual connection cards can be dismissed (AJAX: `tclas_dismiss_connection`)
- Unseen connections drive a notification badge

### Sub-pages (all gated)
- `/member-hub/profiles/` — directory and individual profiles
- `/member-hub/my-story/` — genealogy story form
- `/member-hub/documents/` — members-only documents
- `/member-hub/resources/` — members-only resources

---

## 14. Luxembourgish Language Tagger

**Location**: `inc/ltz-tagger.php`

### What it does
Wraps known Luxembourgish words/phrases in `<abbr lang="lb" class="ltz">` (or equivalent) to signal the language switch to screen readers and enable tooltip pronunciation display.

### Specifics
- Applied via `the_content` and `the_excerpt` filters (priority 20)
- Front-end only (not applied in admin)
- 19 curated terms (e.g., Gudde Moien, Lëtzebuergesch, Moien, etc.)
- Whole-word, case-sensitive regex matching
- Multi-word phrases prioritized (longest-match-first to avoid false positives)
- JS tooltips initialized by `initLtzTooltips()` in `main.js`

---

## 15. LOD.lu Audio Pronunciation

**Location**: `inc/lod-audio.php`

### Purpose
Fetches native Luxembourgish audio pronunciation clips for commune names (displayed in commune popups on the ancestor map and elsewhere).

### API flow
1. **Primary**: LOD.lu SPARQL endpoint (public, no key required)
   - Query: finds `schema:audio` or `mo:recording` for a place by its Luxembourgish label
2. **Fallback**: Forvo API (optional; requires API key in ACF Theme Options)

### Caching
- Transient key: `tclas_lod_audio_{slug}` (7-day expiry)
- Null result cached as empty string to distinguish from cache miss

---

## 16. National Day Season Detection

**Location**: `inc/national-day.php`

### Logic
- **Auto window**: June 16–24 (7 days before + 1 day after June 23, Lëtzebuerger Nationalfeierdag)
- **Manual override**: ACF Theme Options `national_day_mode` checkbox
- Result passed to JS as `tclasData.nationalDay`
- JS (`initNationalDaySeason()`) adds `is-national-day-season` class to `<body>`

---

## 17. AJAX Handlers

All registered via `wp_ajax_*` and `wp_ajax_nopriv_*` where indicated.

| Action | Auth | File | Payload | Response |
|--------|------|------|---------|----------|
| `tclas_save_my_story` | Logged-in + member | inc/connections.php | communes[], surnames[], visibility, open_to_contact, nonce | `{connections_found, message}` |
| `tclas_dismiss_connection` | Logged-in | inc/connections.php | other_user_id, nonce | `{success}` |
| `tclas_mark_connections_seen` | Logged-in | inc/connections.php | nonce | `{success}` |
| `tclas_upload_profile_photo` | Logged-in + member | inc/member-profiles.php | photo (file), nonce | `{photo_url, photo_id}` |
| `tclas_remove_profile_photo` | Logged-in + member | inc/member-profiles.php | nonce | `{success}` |
| `tclas_referral_copy` | Any (nopriv) | inc/referral.php | nonce | `{success}` |
| `lcq_send_results` | Any (nopriv) | plugin/luxembourg-citizenship-quiz/ | email, result_text, nonce | `{success/error, message}` |

**No custom REST API endpoints** are registered. Standard WP REST API is available.

---

## 18. JavaScript — Front-End Behavior

### `assets/js/main.js`

| Init function | Behavior |
|---------------|----------|
| `initMobileNav()` | Toggle hamburger menu; close on outside click |
| `initHubSidebar()` | Toggle member hub sidebar (mobile); ESC to close |
| `initRenewBanner()` | Membership expiry banner dismiss (session storage) |
| `initReferralCopy()` | Copy referral URL to clipboard; visual feedback; fires `tclas_referral_copy` AJAX |
| `initScrollReveal()` | IntersectionObserver fade-in on scroll for eligible elements |
| `initNationalDaySeason()` | Adds `is-national-day-season` to `<body>` during detection window |
| `initDirectoryFilters()` | Filter button clicks dispatch `tclasFilter` custom event |
| `initDropdowns()` | Keyboard navigation for dropdown menus (Enter/Space) |
| `initSmoothScroll()` | Smooth scroll + focus management for anchor links |
| `initLtzTooltips()` | Bootstrap tooltips on `<abbr class="ltz">` elements |
| `initConnectionsPanel()` | Auto-marks connections seen after 4 s; dismiss individual cards via AJAX |
| `initMyStoryForm()` | Repeater field add/remove rows; validation; AJAX submit |

### `assets/js/member-profiles.js`
- Profile card filtering and search
- Photo upload and remove (AJAX with FormData)
- Directory pagination / infinite scroll

### `assets/js/tclas-ancestor-map.js`
- Leaflet map init with Mapbox custom style (raster tiles) or CartoDB fallback
- Commune marker rendering (radius = f(member count))
- Popup with commune name, LOD.lu audio, links to commune profile
- Split layout: map + filtered member list sidebar (member pages)

### `assets/js/hero-greeting.js`
- Accumulating greeting animation: "Bonjour." → "Hello." → "Moien."
- Data-stage/data-active attribute selectors for per-word reveal
- Reduced motion: all words shown immediately

### `assets/js/hero-slideshow.js`
- Dual-photo slideshow with slide-over transitions
- Outgoing image holds stationary; incoming slides over
- Reduced motion: crossfade fallback

### `assets/js/msp-lux-counters.js`
- Animated stat counters on MSP+LUX page
- IntersectionObserver trigger

---

## 19. Asset Enqueuing & Localized Data

**Location**: `inc/enqueue.php`

### CSS (in order)
1. Adobe Fonts (Typekit) — kit ID from ACF `adobe_fonts_kit_id`
2. Bootstrap 5.3.3 — CDN (cdn.jsdelivr.net)
3. `assets/css/main.css` — compiled from SCSS (`assets/scss/main.scss`)
4. `assets/css/tribe-events.css` — conditional, TEC pages only
5. `assets/css/member-profiles.css` — conditional, profiles pages only
6. `assets/css/tclas-ancestor-map.css` — conditional, map pages only

All local assets use `filemtime()` for version strings (auto cache-busting on save).

### JS (in order)
1. Bootstrap 5.3.3 bundle — CDN
2. `assets/js/main.js`
3. WP comment-reply — conditional (open comment threads)

### Localized JS object: `tclasData`

| Key | Value |
|-----|-------|
| `ajaxUrl` | `admin-ajax.php` URL |
| `nonce` | `tclas_nonce` |
| `isLoggedIn` | bool |
| `nationalDay` | `{active: bool, start: string, end: string}` |
| `themeUri` | Theme directory URL |
| `referralUrl` | Current user's referral URL (or empty) |
| `strings` | UI strings: openMenu, closeMenu, copied, etc. |

### Performance
- Preconnect hints: cdn.jsdelivr.net, use.typekit.net, p.typekit.net

---

## 20. Navigation Menus & Widget Areas

### Registered nav menus

| Location ID | Description |
|-------------|-------------|
| `primary` | Main header navigation |
| `footer-main` | Footer — primary links |
| `footer-org` | Footer — organizational links |
| `hub` | Member hub sidebar navigation |

### Registered widget areas

| ID | Location |
|----|----------|
| `hub-sidebar` | Member hub sidebar (page-member-hub.php) |

---

## 21. Image Sizes

| Size name | Dimensions | Crop | Use |
|-----------|-----------|:----:|-----|
| `tclas-card` | 800×533 | Hard | Story/content cards |
| `tclas-hero` | 1440×640 | Hard | Homepage hero (desktop) |
| `tclas-hero-mobile` | 640×640 | Hard | Homepage hero (mobile) |
| `tclas-square` | 600×600 | Hard | Profile photos, square illustrations |
| `tclas-wide` | 1200×400 | Hard | Wide banners |

---

## 22. Page Templates

| Template file | Slug | Notes |
|---------------|------|-------|
| `front-page.php` | / | Homepage (hero slideshow, greeting animation) |
| `page-templates/page-about.php` | /about | About page with board sidebar |
| `page-templates/page-ancestry.php` | /ancestry | Ancestry — two-column layout (steps + sticky map) |
| `page-templates/page-citizenship.php` | /citizenship | Citizenship quiz + pathways |
| `page-templates/page-communes.php` | /member-hub/ancestral-map/commune | Communes A-Z index |
| `page-templates/page-donate.php` | /donate | Donate page (GiveWP integration) |
| `page-templates/page-join.php` | /join | Membership + volunteer tracks, tier cards |
| `page-templates/page-map.php` | /member-hub/ancestral-map | Full ancestral map (members) |
| `page-templates/page-member-hub.php` | /member-hub | Gated dashboard |
| `page-templates/page-member-profiles.php` | /member-hub/profiles | Directory + individual profiles |
| `page-templates/page-msp-lux.php` | /msp-lux | MSP+LUX connections |
| `page-templates/page-my-story.php` | /my-story | Genealogy story form |
| `page-templates/page-newsletter.php` | /newsletter | Loon & Lion archive + sticky subnav |
| `page-templates/page-referral-welcome.php` | /welcome | Referral landing page |
| `page-templates/page-documents.php` | /member-hub/documents | Gated documents |
| `page-templates/page-resources.php` | /member-hub/resources | Gated resources |
| `taxonomy-tclas_commune.php` | /member-hub/ancestral-map/commune/{slug} | Commune profile page |

---

## 23. wp-options & WP-Cron

### Custom wp-options

| Option key | Type | Purpose |
|------------|------|---------|
| `tclas_unresolved_entries` | serialized array | Queue of genealogy entries pending admin review |
| `tclas_connection_batch_offset` | int | Pagination offset for nightly connection cron |

### WP-Cron jobs

| Hook | Schedule | Function | Purpose |
|------|----------|----------|---------|
| `tclas_connection_cron` | Daily @ 2 a.m. | `tclas_run_connection_cron()` | Re-compute connections for all members in batches of 50 |

---

## 24. Security & Nonces

### Nonces registered

| Nonce handle | Used by |
|--------------|---------|
| `tclas_nonce` | General theme AJAX (localized in `tclasData`) |
| `tclas_my_story_nonce` | My Story form submission |
| `tclas_event_members_only_nonce` | Event meta box save |
| `lcq_email_nonce` | Quiz email submission |

### Input sanitization practices
- `$_POST` / `$_GET`: sanitized with appropriate `sanitize_*` functions
- Emails: `is_email()` + `sanitize_email()`
- URLs: `esc_url()`
- HTML in user content: `wp_kses_post()`
- User meta strings: `sanitize_text_field()`, `sanitize_key()`

### Capability checks
- Member-gated AJAX: `tclas_is_member()` + `is_user_logged_in()`
- Board-only visibility: checks PMPro level name = 'Board'
- Admin screens: `current_user_can('manage_options')`

---

## 25. Admin Extensions

### TCLAS admin menu — Unresolved Genealogy Entries
- **Location**: top-level admin menu item "TCLAS"
- **Function**: `tclas_admin_unresolved_page()`
- **Purpose**: Manual review queue for genealogy entries that failed fuzzy matching
- **Storage**: `tclas_unresolved_entries` wp-option
- **Admin actions**:
  - **Approve** — suggests addition to surname cluster data in `connection-data.php`
  - **Dismiss** — removes entry from queue

---

*Originally generated 2026-02-28. Updated 2026-03-08. Source: theme `inc/` directory, custom plugin, and ACF configuration.*
