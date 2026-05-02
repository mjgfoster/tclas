# Phase 2 — Legal Page Reversions

The May 9, 2026 launch ships only Home, Events, About, Contact, and Legal pages. The legal pages were originally drafted to cover the full TCLAS feature set (member accounts, newsletter, ancestral map, citizenship quiz, messaging, etc.). For launch, all references to those features were removed so the policies don't claim to govern functionality that isn't live yet.

**This file lists what was removed and what to restore when each Phase-2 feature ships.** Once a feature goes live, restore the relevant sections in the corresponding policy and bump the "Last Updated" date. The pre-trim originals are recoverable from the WordPress post revisions for each page (Edit Privacy Policy → Document → Revisions) or from this file's git history.

---

## Privacy Policy (page ID 3, slug `privacy`)

Trimmed: 2026-04-25. Effective + Last Updated set to May 1, 2026.

### Newsletter signup ships
- Restore §2.A "Newsletter Signup" data collection block (email, optional name)
- Restore Brevo (or successor email platform) entry in §4 Third-Party Services with correct data-shared / retention / privacy-policy link
- Restore §6 Data Retention row: "Newsletter signup — Until unsubscribe"
- Restore §10 Cookies row: "Marketing (email-platform) — Track email engagement"
- Restore §15.C "Marketing emails" + email-platform rows in the Opt-Out Summary

### Membership accounts (PMPro) ship
- Restore §2.A "Membership Account" block (name/email/address/phone, payment info, tier, profile, referral code)
- Restore §3.A items: Membership Management, Member Directory & Profiles, Referral Program
- Restore §4 Payment Processing (PMPro / Stripe) entry
- Restore §5 "Member Data & Access Controls" entire section (directory, story submissions, quiz access)
- Restore §6 Data Retention rows: "Membership account — duration + 7 years" and "Payment/billing — 7 years" and "Member profiles — until deletion"
- Restore §8.A "No credit card storage" line referring to Stripe

### Citizenship Eligibility Quiz ships
- Restore §2.A "Citizenship Eligibility Quiz" data collection (family tree, results email)
- Restore §3.A "Citizenship Quiz" use-case
- Restore §5.C "Quiz & Eligibility Data" privacy block
- Restore §6 row: "Quiz responses — Until account deletion"

### Ancestral Map (Mapbox) ships
- Restore §2.A "Mapbox Usage" block (commune lookups, interactions)
- Restore §2.B "Third-Party Services" Mapbox entry
- Restore §4 Third-Party "Mapbox" entry with link to their privacy policy

### Member story submissions ("My Story") ship
- Restore §2.A "Member Story Submission" block
- Restore §5.B "Story Submissions" privacy block

---

## Terms of Use (page ID 180, slug `terms`)

Trimmed: 2026-04-25. Effective + Last Updated set to May 1, 2026. Section count went from 17 to 14; survival list in §12 (Termination) was renumbered accordingly.

### Membership accounts (PMPro) ship
- Restore the original §4 "Membership" section in full (Tiers & Dues, Account Responsibilities, Member Benefits, Cancellation), including the `/join/` link
- Restore "membership, event registration, story submissions" + 18-or-parental-consent language in the Eligibility section
- Restore the dues-based liability cap in Limitation of Liability: replace "$100 USD" with "the amount of membership dues you paid in the 12 months preceding the claim"
- Renumber subsequent sections and update §12 (Termination) survival list

### Citizenship Eligibility Quiz ships
- Restore the original §5 "Citizenship Eligibility Quiz" disclaimer section
- Restore the "Decisions made based on citizenship quiz results" bullet in Limitation of Liability
- Restore §5 in §12 (Termination) survival list

### Member content submissions ("My Story" / Member Profiles) ship
- Restore the original §6 "User-Generated Content" section (My Story license grant, Member Profiles, Prohibited Content). Note: the launch-state Terms keep a generalized "Acceptable Use" section (currently §4) that covers the contact-form case — when UGC ships, expand that section into the full UGC block from the original

### Newsletter (The Loon & The Lion) ships
- Restore §8.A reference to newsletter content "The Loon & The Lion" in Intellectual Property
- Restore §8.C "Newsletter Content" subsection (sharing-links policy, full-article reproduction permission)

### Referral Program ships
- Restore the original §7 "Referral Program" section

---

## Accessibility Statement (page ID 177, slug `accessibility`)

Trimmed: 2026-04-25. Last Updated set to May 1, 2026.

Only one Phase-2 reference was removed: the "Interactive map" item in the "Known Limitations" list. When the ancestral commune map (Mapbox) ships, restore that bullet to the Known Limitations list.
