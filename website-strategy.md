# TCLAS Website & Email Strategy

_Updated 2026-03-08_

## Overview

TCLAS (Twin Cities Luxembourg American Society) is a membership nonprofit launching in April 2026. The website serves as the primary digital hub — attracting visitors through content, converting them into email subscribers, and nurturing subscribers toward membership.

## Website Architecture

**Platform**: WordPress with custom theme "Clausen" v1.2 ("Ciel Bleu")
**Key plugins**: Paid Memberships Pro (membership tiers), ACF Pro (content management), The Events Calendar, Brevo (email marketing), WP Recipe Maker, Luxembourg Citizenship Quiz (custom)

### Core pages and their roles

| Page | Purpose | Funnel role |
|------|---------|-------------|
| Homepage | First impression, hero + CTAs | Awareness |
| MSP+LUX | Cultural connection to Minneapolis & Luxembourg | Awareness / Interest |
| Citizenship | Luxembourg citizenship pathways + eligibility quiz | Lead magnet |
| Ancestry | Genealogy research resources + commune map | Lead magnet |
| Newsletter | Archive of past issues, browse by topic | Interest / Nurture |
| Join | Membership tiers, benefits, referral program | Conversion |
| Events | Calendar of upcoming and past events | Engagement / Retention |
| Member Hub | Profiles, connections, badges | Retention |

### Membership tiers (PMPro)

- Individual: $30/yr
- Family: $45/yr
- Student: $15/yr

## Email List Strategy

**Platform**: Brevo (integrated via Brevo WP plugin, slug: `mailin`)
**Member sync**: FuseWP (premium plugin; handles PMPro ↔ Brevo sync — subscribe on checkout, remove on cancellation)

### Subscriber acquisition points

1. **Footer signup form** — sitewide, persistent (Brevo form, ID set in Theme Options `footer_newsletter_form_id`)
2. **Citizenship quiz results** — email capture after quiz completion; adds to Brevo list (`lcq_brevo_list_id` wp-option) and tags with `QUIZ_COMPLETER` attribute
3. **Newsletter page** — CTA alongside issue archive
4. **Events** — post-event follow-up potential

### Content → Email → Membership funnel

```
Visitor discovers site (SEO, social, word of mouth)
  ↓
Engages with pillar content (Citizenship, Ancestry, MSP+LUX)
  ↓
Subscribes to newsletter (footer form, quiz email capture)
  ↓
Receives regular newsletter issues (Brevo campaigns)
  ↓
Attends an event or explores member benefits
  ↓
Joins as a member (PMPro checkout)
  ↓
Engages in community (profiles, hub, badges, messaging)
  ↓
Refers others (Join page referral banner for existing members)
```

### Newsletter content model

The site already has a newsletter/issue system with:
- **Issue dates** (YYYY-MM grouping)
- **Issue titles** (e.g., "The Loon & the Lion — March 2026")
- **Departments** (taxonomy-based topic categorization, bilingual English/Lëtzebuergesch)
- **Article ordering** within issues

This maps directly to Brevo campaigns — each issue becomes an email, and the website newsletter archive serves as the permanent, browsable home for all content.

### Recommended Brevo setup

- **One primary list** — all subscribers
- **Tags or attributes** for segmentation: quiz completers (`QUIZ_COMPLETER`), event attendees, members (sync via FuseWP)
- **Automation**: welcome sequence for new subscribers (introduce TCLAS, highlight key content, soft CTA to join)
- **Campaign cadence**: monthly newsletter aligned with website issue schedule

## Integration Points

### Website → Brevo
- Brevo form submissions add subscribers
- Quiz email handler adds to Brevo list + sets `QUIZ_COMPLETER` attribute
- Event RSVPs could feed subscriber list (if registration URL points to a form)

### Brevo → Website
- Newsletter emails link back to full articles on the site
- Campaign CTAs drive to /join/, /events/, /citizenship/
- Member-only content teasers drive upgrades

### PMPro → Brevo (via FuseWP)
- FuseWP syncs PMPro membership status to Brevo lists/attributes automatically
- Enables: "members-only" email segments, renewal reminders, lapsed member re-engagement

## Key dates

- **March 12**: Copy + UI review with Rebecca
- **March 22**: Board preview of website
- **April 18**: Soft launch

## Open items

- [ ] Enter Brevo API key in WP Admin > Brevo > Settings
- [ ] Create Brevo signup form, set form ID in Theme Options (`footer_newsletter_form_id`)
- [ ] Install FuseWP for PMPro ↔ Brevo member sync
- [ ] Set quiz Brevo list ID (`lcq_brevo_list_id` wp-option)
- [ ] Create `QUIZ_COMPLETER` boolean attribute in Brevo account
- [ ] Plan first newsletter issue content for launch
- [ ] Draft welcome email sequence (3–5 emails)
- [ ] Define email-exclusive vs. website-published content balance
