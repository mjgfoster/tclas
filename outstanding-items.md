# TCLAS Outstanding Items

_Updated 2026-03-11_

---

## 1. Post-Merge Brevo Setup (You)

These are manual configuration steps now that the Mailchimp to Brevo code migration is merged.

- [ ] **Brevo API key** — Plugin is installed (`mailin`); enter API key in WP Admin > Brevo > Settings
- [ ] **Create signup form** — Build a Brevo form in WP Admin > Brevo > Forms; note the form ID
- [ ] **Set form ID in Theme Options** — ACF field `footer_newsletter_form_id` > enter the Brevo form ID
- [ ] **Install FuseWP** — Premium plugin; handles PMPro <> Brevo member sync (subscribe on checkout, remove on cancellation). This replaces the custom Mailchimp hooks we removed.
- [ ] **Set quiz Brevo list ID** — `wp_options` key `lcq_brevo_list_id` — the Brevo list that quiz email captures go into
- [ ] **Create `QUIZ_COMPLETER` attribute in Brevo** — The quiz handler tags contacts with this attribute; it must exist in your Brevo account as a boolean attribute
- [ ] **Deactivate old plugins** — Confirm `mc4wp-mailchimp-for-wp` and `pmpro-mailchimp` are deactivated (should already be done)

---

## 2. Remaining User-Created Content

Stub pages and copy that only you can write.

| Item | Where it goes | Target |
|------|---------------|--------|
| FAQ page content | `/faq/` | Phase 5 |
| Mission page content | `/about/mission/` | Phase 5 |
| History page content | `/about/history/` | Phase 5 |
| Contact page content | (create page) | Phase 5 |
| Financials page content | `/about/financials/` | Phase 5 |
| MSP+LUX pillar descriptions | `/msp-lux/` — connection info, resource URLs | **Mar 12** |
| Ancestry step copy & resource links | `/ancestry/` — research resource URLs are `href="#"` | **Mar 12** |
| Citizenship pathways copy | `/citizenship/` — Article 7, 23, 7+23 descriptions | **Mar 12** |
| Volunteer description | `/join/` — volunteer section | **Mar 12** |

---

## 3. Remaining User Tasks (Assets & Config)

- [ ] **Department icons** — Needed for Newsletter "Browse by Topic" section (target: Apr 3)
- [ ] **Instagram + LinkedIn URLs** — Footer fields render conditionally; enter URLs in Theme Options when accounts are live
- [ ] **Homepage stats** — ACF repeater is built; add values when confirmed (stat_value + stat_label)
- [ ] **Testimonials** — ACF repeater is built; add entries when content exists (name, member_since, location, photo, quote)
- [ ] **GiveWP setup** — Donate page template is built; install GiveWP, create form, set form ID in Theme Options, create WP page at `/donate/`

---

## 4. Staging & Production Checklist

**Staging URL**: https://stage.twincities.lu/

### After Migration (any environment)

- [ ] Flush permalinks (Settings > Permalinks > Save) — re-registers custom rewrite rules after domain change
- [ ] Verify `/member-hub/profiles/{username}/` loads correctly
- [ ] Verify `/newsletter/issue/YYYY-MM/` loads correctly
- [ ] Verify `/member-hub/ancestral-map/commune/{slug}/` loads correctly

### Security Optimizer (SiteGround)

- [ ] Confirm `admin-ajax.php` is not blocked — test quiz email submission on `/citizenship/`
- [ ] Test profile photo upload on `/my-story/`
- [ ] Test member connection actions on `/member-hub/` (dismiss, mark seen)
- [ ] If URL filtering is enabled, whitelist `/member-hub/profiles/` and `/newsletter/issue/` patterns

### Speed Optimizer (SiteGround)

- [ ] Exclude pages 22 (member-hub), 85 (my-story), 94 (profiles) from page cache — they render user-specific content
- [ ] Verify `sgo_js_minify_exclude` / `sgo_css_minify_exclude` filters in `inc/enqueue.php` protect Typekit + jsDelivr CDN assets
- [ ] Test that combined/minified JS doesn't break: hero slideshow, quiz, ancestor map, Leaflet

### DNS & CDN

- [ ] DNS pointed at SiteGround (nameservers switched back from Cloudflare 2026-03-08)
- [ ] Cloudflare CDN/proxy deferred to post-launch — was interfering with email and staging

### Other Production Setup

- [ ] **Site Kit by Google** — Installed but deactivated locally; activate on production and connect via Google for Nonprofits
- [ ] **WP Mail SMTP** — Configure Gmail/Google Workspace OAuth mailer on staging/production
- [ ] **Complianz** — GDPR cookie banner is installed; review settings for EU compliance (Luxembourg audience)
- [ ] **The SEO Framework** — Active; verify OG/Twitter Card meta renders correctly on production domain

---

## 5. Design & UX Backlog

- [ ] **Homepage navbar branding** — Compact logo ("TCLAS") doesn't show the full org name to first-time visitors. Consider: full-name logo on homepage only, tagline subtitle, transparent overlay navbar, or letting the hero handle introductions. Direction TBD.
- [ ] **Modern Footnotes styling** — Plugin works but tooltip/footnote styling hasn't been themed to Ciel Bleu
- [ ] **Inline styles cleanup** — Heavy inline styles in `header.php` and `footer.php` should move to CSS classes (from deferred-fixes.md)

---

## 6. Parking Lot (Deferred — Not Blocking Launch)

### PMPro Gift Memberships
- Official free add-on — install when ready
- Allows purchasing a membership on someone else's behalf; recipient gets redemption email
- Natural complement to the referral system on the Join page

### `tclas-member-messaging` (Custom Plugin)
- Lightweight DM system for ~500 members
- Custom DB table `tclas_messages`: id, sender_id, recipient_id, body, sent_at, read_at, sender_deleted, recipient_deleted
- No threading, no subjects — conversation view between two users
- Unread count badge on Member Hub nav
- Email alert on receipt (opt-in via `tclas_notify_dms` user meta)
- Not started

### Map Match Email Alerts
- `tclas_notify_map_matches` user meta (default opt-in)
- Triggers when another member pins the same ancestral commune
- Hook TBD pending review of ancestral map plugin

### CTA Library (`tclas_cta` CPT)
- Non-public custom post type for reusable calls-to-action
- Each entry: headline, body copy, button label, button URL, style variant
- Per-page ACF field + sitewide fallback in Theme Options
- Spec outlined but not yet implemented

---

## 7. Build Program Status

| Phase | Status | Notes |
|-------|--------|-------|
| Phase 0 | Done | Foundation |
| Phase 1 | Done | Core templates |
| Phase 2 | Done | Member features |
| Phase 3 | Done | Pillar pages (MSP+LUX, Citizenship, Join, Ancestry) |
| Phase 4 | Done | Events archive + newsletter sticky subnav |
| Phase 5 | Pending | Stub page content (FAQ, Mission, History, Contact, Financials) — **user-authored** |

### Key Dates

- **Copy + UI review with Rebecca**: Thursday, March 12
- **Board preview deadline**: March 22
- **Soft launch**: April 18

---

## 8. Recently Completed

- Commune data enrichment — imported 583 places with municipality, Luxembourgish name, canton, coordinates from official data; cascaded Wikipedia + official website URLs (484/583 and 481/583 coverage)
- Municipality in commune subtitle — shows "Käerjeng, Capellen Canton" when municipality differs from place name
- External links moved into Gemeng/Municipality fact row on commune profiles
- "Luxembourg" renamed to "Luxembourg City" across all 31 municipality references
- Commune profile pages (three-column layout, LOD.lu audio, custom breadcrumbs)
- Communes A-Z index at `/member-hub/ancestral-map/commune/`
- Split ancestral map layout (map + filtered member list)
- LOD.lu REST API integration for Luxembourgish pronunciation audio
- WCAG link color audit — `--c-crimson` for all links on light backgrounds
- Commune URL restructuring (`/member-hub/ancestral-map/commune/{slug}/`)
- Donate page template + GiveWP integration (Stripe)
- Mailchimp to Brevo migration (all PHP, SCSS, ACF fields)
- Modern Footnotes plugin installed
- Member badges system
- Staging site uploaded to `stage.twincities.lu`
