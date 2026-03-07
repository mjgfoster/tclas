# TCLAS Website & Email Strategy

## Overview

TCLAS (The Clausen Luxembourg-American Society) is a membership nonprofit launching in April 2026. The website serves as the primary digital hub — attracting visitors through content, converting them into email subscribers, and nurturing subscribers toward membership.

## Website Architecture

**Platform**: WordPress with custom theme ("Ciel Bleu")
**Key plugins**: Paid Memberships Pro (membership tiers), ACF Pro (content management), The Events Calendar, Mailchimp for WP, WP Recipe Maker

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

**Platform**: Mailchimp (integrated via MC4WP plugin)

### Subscriber acquisition points

1. **Footer signup form** — sitewide, persistent (MC4WP form, ID set in Theme Options)
2. **Citizenship quiz results** — email capture after quiz completion (existing AJAX handler)
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
Receives regular newsletter issues (Mailchimp campaigns)
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

This maps directly to Mailchimp campaigns — each issue becomes an email, and the website newsletter archive serves as the permanent, browsable home for all content.

### Recommended Mailchimp setup

- **One primary list** — all subscribers
- **Tags or groups** for segmentation: quiz completers, event attendees, members (sync via PMPro)
- **Automation**: welcome sequence for new subscribers (introduce TCLAS, highlight key content, soft CTA to join)
- **Campaign cadence**: monthly newsletter aligned with website issue schedule

## Integration Points

### Website → Mailchimp
- MC4WP form submissions add subscribers
- Quiz email handler can add to list + tag as "quiz-completer"
- Event RSVPs could feed subscriber list (if registration URL points to a form)

### Mailchimp → Website
- Newsletter emails link back to full articles on the site
- Campaign CTAs drive to /join/, /events/, /citizenship/
- Member-only content teasers drive upgrades

### PMPro → Mailchimp
- PMPro has Mailchimp integration add-ons — sync members to a tag/group automatically
- Enables: "members-only" email segments, renewal reminders, lapsed member re-engagement

## Key dates

- **March 22**: Board preview of website
- **April 18**: Soft launch

## Open items

- [ ] Create MC4WP form in WP Admin, set form ID in Theme Options
- [ ] Set up Mailchimp list and welcome automation
- [ ] Decide on PMPro ↔ Mailchimp sync (free add-on available)
- [ ] Plan first newsletter issue content for launch
- [ ] Configure quiz email handler to also subscribe to Mailchimp
- [ ] Draft welcome email sequence (3–5 emails)
- [ ] Define email-exclusive vs. website-published content balance
