# Homepage hero image guidelines

Editorial and technical guidelines for the rotating photo pairs in the TCLAS homepage hero.

## What this is

The homepage hero shows **paired photos** — one from Minnesota (left) and one from Luxembourg (right) — that gently slide in from opposite sides every few seconds. Up to **6 pairs** rotate continuously. Each pair is a moment of visual rhyme: similar mood, season, scale, or activity, so the eye gets a payoff each time the next pair lands.

Photos are managed in **WP admin → Theme options → Hero photo pairs** (ACF field: `hero_pairs`).

## Aspect ratio and dimensions

- **Desktop photos:** 1600 × 900 px (16:9 landscape). Smaller than 1600 px wide will look soft on retina screens.
- **Mobile image** (optional, one per pair): portrait or square crop. Falls back to the Luxembourg photo if left blank. Add a mobile image when the LUX photo's important subject sits dead-center horizontally and would get cut off when squeezed to phone width.
- **File format:** JPG for photos, PNG only when transparency is needed. WebP is even better if your tool exports it.
- **File size:** aim for **under 400 KB** per image. SiteGround's SG Optimizer will compress further on upload, but starting under 400 KB keeps the hero snappy.

## Composition

The hero has **angled inner edges** (clip-path polygons cut a couple of degrees off the inner edges where the two photos meet). Don't put critical subjects within roughly 3 rem of the inner edge — they'll get clipped:

- **Minnesota photo:** keep critical subjects out of the **bottom-right corner**.
- **Luxembourg photo:** keep critical subjects out of the **top-left corner**.

Also leave a **clear bottom corner for the municipality label** — bottom-left on MN, bottom-right on LUX. The label sits over a translucent dark backdrop, so it works over most images, but a busy area there reads as visual noise.

## Pairing the photos

This is the editorial work. Each row is a *deliberate pair*:

- **Same scale:** skyline ↔ skyline, person ↔ person, cobblestone ↔ cobblestone — not skyline ↔ teacup.
- **Same season or weather** wherever you can — a sunny MN summer shouldn't sit next to snowy Luxembourg. Mismatches are jarring during the cycle.
- **Same time of day** is bonus, not required.
- **Avoid identical compositions in adjacent pairs** — if pair 1 is two cityscapes, make pair 2 something else (a market, a riverbank, a family) so the rotation has variety.

## Required fields (per pair)

| Field | Required? | Notes |
|---|---|---|
| Minnesota photo | yes | 1600×900, 16:9 |
| Minnesota municipality | yes | e.g. "Minneapolis", "Saint Paul", "Stillwater" |
| Minnesota photo credit | no | format as "Photo: First Last" or "Photo: TCLAS member" |
| Luxembourg photo | yes | 1600×900, 16:9 |
| Luxembourg municipality | yes | e.g. "Luxembourg City", "Echternach", "Vianden" |
| Luxembourg photo credit | no | same format as MN credit |
| Mobile image | no | portrait/square crop; falls back to LUX photo |

## Things to avoid

- **Low contrast images** where the municipality label would be unreadable — test the bottom-corner area against the dark label backdrop.
- **Photos with embedded text** (signs, posters, watermarks) — the municipality labels carry the only text the hero should show.
- **Headshots or portraits** as the MN or LUX photo — these are place pairings, not people pairings. Save portraits for stories or board pages.
- **Identical or near-identical pairings twice** — six unique pairs is plenty of variety; near-duplicates feel like a bug.

## Accessibility

Images render with `aria-hidden="true"` and no `alt` text — they're decorative. The municipality labels carry all the semantic information. So you don't need to describe photo content in alt text, but the municipality field MUST be filled in.

## Special seasonal mode

During **June** (within 7 days of June 23, Lëtzebuerger Nationalfeierdag), a flag stripe appears across the top and the hero gets a special treatment. No editor action needed — it's auto-detected.

## Photoshop / Affinity template specs

Quick reference if you're building a master template:

- **Canvas:** 1600 × 900 px, 72 dpi, sRGB
- **Safe zone for label** (bottom-left for MN, bottom-right for LUX): roughly a 250 × 80 px reserved area, ~24 px in from the corner — keep textures and busy detail out of it.
- **Inner-edge mask:** the inner edge of each photo gets clipped to an angled polygon. To preview the on-page crop, draw a polygon mask:
  - MN: full rectangle minus the **bottom-right** corner cut at ~2.5 rem (≈ 40 px on the rendered hero — proportionally about 4% of the 1600 px width)
  - LUX: full rectangle minus the **top-left** corner cut at ~2.5 rem
- **Test pair:** export both at 1600×900 and place side-by-side at 50% width each — that's how they'll appear on a wide desktop. Anything that lands within ~64 px of the inner edge is at risk of clipping.
