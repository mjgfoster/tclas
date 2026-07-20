# Plan: Personalized Citizenship Packet PDF (quiz v2.1)

**Status:** planned, not started. Post-launch-party project (party is 2026-07-22; this is a fall/later item).
**Idea (2026-07-10):** at the end of the citizenship quiz, offer a downloadable, personalized PDF packet — the quiz-taker's pathway, the process steps, and a per-generation vital-records checklist with ruled blanks for names/dates/places. Doubles as a durable, printable reminder of TCLAS (logo + URL + membership nudge in the footer).

## Why it works with what exists

The quiz JS state at outcome time already contains everything needed, with **no PII**:

- outcome type: `article7` | `article23` (living) | posthumous two-phase
- `chosenSide`, `userBornBefore1969`
- `lineage[]`: per generation `{ label, gender, bornBefore1969, bornInLux }`

The PDF is generated from the *shape* of the tree; the blanks are where the user adds personal details on paper. Nothing is stored server-side.

## UX

- On eligible outcomes, replace "Send yourself a copy of these results" with:
  **"Get your personalized citizenship packet (PDF)"** — email field → sends results email *with PDF attached* (existing Brevo subscribe + `quiz-completer` tag flow stays) → also triggers immediate browser download.
- Secondary quiet link: "or just download the PDF" (no email required — the generosity is the brand; don't hard-gate).
- Ineligible/unsure outcomes: no packet in v2.1. (Parked idea: a "research starter" one-pager for the unsure-ancestor outcomes.)

## Architecture

- **dompdf** via Composer inside this plugin (HTML/CSS templating → reuse clausen design tokens + TCLAS logo, no PDF drawing API).
- One AJAX endpoint (`lcq_generate_pdf`, nonce'd, works for `nopriv`) accepting the quiz-state JSON.
- **Server-side validation is mandatory** — this must not become an arbitrary-PDF service:
  - cap lineage at 7 entries; whitelist labels to the exact `genLabel()` vocabulary; booleans only for flags; outcome type from a fixed enum.
  - All rendered text comes from server-side templates keyed by validated state — never echo client strings into the PDF.
- Email path: reuse `lcq_handle_email_submission`, write PDF to a temp file for the `wp_mail` attachment, delete after send.

## PDF contents

1. **Your path** — outcome headline + pathway explanation; lineage chain rendered as a diagram (mirror the sidebar family-tree, print-styled).
2. **The steps** — numbered process per pathway. NEEDS AUTHORING + VETTING (biggest lift; a printed doc reads as authoritative):
   - Article 7: by-mail process, no language test/residency.
   - Article 23 (living relative): relative's Article 7 recognition first, then applicant's Article 23; in-person Luxembourg City appointment, ~4-month wait.
   - Posthumous: Phase 1 posthumous recognition petition, Phase 2 Article 23.
   - Cross-cutting: certified copies, apostilles, translations, where to request records (ANLux, parish registers, US county offices).
3. **Records checklist** — derived mechanically from `lineage[]`. For each generation from the Luxembourg-born ancestor down to the applicant: birth record, marriage record (the link to the next generation), death record if applicable; plus applicant's own documents. Each item: checkbox + ruled blanks (full name, date, place, "where to find it" hint). Headings use the person's actual label ("Your great-grandfather — birth record, Luxembourg").
4. **Footer on every page:** legal disclaimer (same text as quiz outcome screen — required), TCLAS logo/URL, membership invitation.

## Build order (rough estimates)

1. Content authoring: per-pathway steps + records requirements (~1 day writing, then fact-check pass — treat like the quiz-logic vetting).
2. dompdf plumbing: composer dep, endpoint, validation, download + email attachment (~few hours).
3. Branded HTML→PDF template with lineage diagram + generated checklist (~half day, mostly polish).
4. QA: every outcome type × lineage depths 1–7; long labels ("great-great-great-…") layout; email attachment via local mailer guard (mail redirects to Matthew locally — see repo dev notes).

## Deferred / decided-against

- **Fillable AcroForm fields:** meaningfully more work; printable ruled blanks are the 80/20 and suit a multi-month paper record hunt. Revisit only if requested.
- Multi-step wizardization of anything: no (established preference for single-page flows).
