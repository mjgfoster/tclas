# TCLAS Launch Checklist — Nationalfeierdag, June 23, 2026

**Plan:** staging5.twincities.lu is the launch candidate. **Content/config freeze: Monday EOD.** Whatever staging5 is at the freeze is what goes live June 23.

**Ground rule now that real members exist on staging5:** staging5 is the **data source of truth**. Only code/files flow *up* to it; data flows *down* (staging5 → Local). **Never push Local's database to staging5 again** — it would overwrite real memberships. (See `DEVELOPMENT.md`.)

`[x]` = done in code (verify it's deployed to staging5).  `[ ]` = to do on staging5.

---

## A. Content freeze — by Monday EOD

- [ ] **Legal pages** — full Privacy Policy + Terms are posted (un-trimmed versions), and their **Effective / Last Updated dates set to June 23, 2026**. (Restored content; confirm it's the full text on staging5, not the trimmed MVP version.)
- [x] **Luxembourg Stories removed** — `tclas_story` CPT + templates gone in code. On staging5: confirm `/stories/` 404s, no story posts remain, the "Luxembourg stories" page is trashed, and permalinks are re-saved (rewrite flush).
- [ ] **Mission + Financials** — folded into About for now (no standalone pages launching). Confirm they're not published/linked.
- [ ] **Fundraising Campaign** — stays a draft (membership-only launch).
- [ ] **Board review of page text** complete; all edits made on staging5 (the master).
- [ ] **Nav + homepage** — every menu item resolves; the "MSP + LUX" dropdown works (button toggle + drawer accordion); no dead links.

## B. Membership & payments

- [x] **Live Stripe works end-to-end** — confirmed with a real $50 charge.
- [ ] **Tiers & prices correct** — Individual $50 / Household $100 / Student-Senior $25 / Benefactor $1,000+, and the Join page buttons map to the right level IDs.
- [ ] **Benefactor custom amount** charges what's entered, with the $1,000 server-side minimum enforced.
- [ ] **Recurring auto-renew checkbox** behaves (recurring vs one-time + expiry).
- [ ] **Welcome / receipt email** — a new member gets a good confirmation email (test by watching one of the board signups land).
- [ ] **Board signups** ("pre-launch UX party") — board members joined successfully.
- [ ] _(Post-launch, low priority)_ tidy the leftover `admin_cancelled`/`expired` rows on the matthew.foster account.

## C. Member features & privacy

- [x] **Expiry cuts access at the deadline** — real-time end-date check (no longer waits on cron).
- [x] **Lapsed-member UX** — the gate shows a "welcome back, renew" banner instead of a cold join page.
- [x] **Public ancestral map leaks no surnames**; privacy toggles (stats/communes/surnames) honored.
- [ ] **Walk the member hub as a real member** (not admin) — hub, directory, edit profile, privacy settings, map editor all work.
- [ ] **`lapsed-demo` test user absent** on staging5. ✓ (deleted)

## D. Security & hardening

- [ ] **PMPro daily cron** on SiteGround (Site Tools → Cron → hit `wp-cron.php`) so expiration *emails* + status flips run. (Access already cuts off in real time, but emails/reporting need this.)
- [ ] **User-enumeration** — enable SG Security's block (optional but recommended now that members exist).
- [ ] **Mapbox token** restricted to the live domain in the Mapbox dashboard.
- [ ] _(Housekeeping)_ consider gitignoring `sgs_encrypt_key.php`.

## E. Go-live mechanics — June 23

- [ ] **Confirm the cutover mechanism** — how does staging5 become the live `twincities.lu` site? (Promote staging5, or push staging5 → production DB+files?) **Decide and document this** — it's the one launch-day unknown.
- [ ] **Full backup** taken immediately before cutover.
- [ ] **Latest code deployed** — staging5 reflects the current git branch (membership-expiry fix, ltz, nav, privacy, etc.).
- [ ] **Flip `noindex` OFF** on the live site (staging5 was kept private/noindex — production must be indexable). Keep any remaining staging environment private.
- [ ] **Purge caches** (SG Optimizer) + re-save permalinks.
- [ ] **Smoke test live:** homepage, Join → checkout, member hub, ancestral map (public = no surnames), legal pages, contact form, an event.
- [ ] **Announce** 🇱🇺

---

_Related: `DEVELOPMENT.md` (dev/deploy protocol), `LAUNCH_PHASE2_REVERSIONS.md` (legal restore details)._
