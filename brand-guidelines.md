# TCLAS Brand Guidelines

## Color Palette

All colors are CSS custom properties defined in `:root {}` (`assets/scss/partials/_config.scss`).

### Brand Blues
| Token | Hex | Use |
|-------|-----|-----|
| `--c-ardoise` | `#2B6282` | Primary brand blue — headings, buttons, footer, dark sections |
| `--c-ardoise-dk` | `#1E4A66` | Hover states, dropdown backgrounds |

### Reds
| Token | Hex | Use |
|-------|-----|-----|
| `--c-crimson` | `#8B3A3A` | Secondary CTAs, active nav state, alerts |
| `--c-crimson-dk` | `#6B2A2A` | Hover states on crimson elements |

### Warm Neutrals
| Token | Hex | Use |
|-------|-----|-----|
| `--c-or-pale` | `#FAF9F6` | Light backgrounds, text on dark surfaces |
| `--c-or-pale-dk` | `#E8D9C5` | Card surfaces, subtle backgrounds |
| `--c-gold` | `#F4A460` | Minnesota Gold — join bar, accent highlights |
| `--c-vert` | `#3D6B4F` | Success states, positive indicators |

### Text & Surfaces
| Token | Hex | Use |
|-------|-----|-----|
| `--c-body-bg` | `#FAF9F6` | Page background (Limestone White) |
| `--c-body-text` | `#454F55` | Primary body copy |
| `--c-muted` | `#626D78` | Secondary/helper text |
| `--c-border` | `#DDD5C4` | Dividers, subtle borders |
| `--c-input-border` | `#C4B89A` | Form field borders |

### Shadows
| Token | Value |
|-------|-------|
| `--shadow-sm` | `0 1px 4px rgba(43,98,130,.10)` |
| `--shadow-md` | `0 4px 16px rgba(43,98,130,.14)` |
| `--shadow-lg` | `0 8px 32px rgba(43,98,130,.18)` |

---

## Typography

### Fonts (Adobe Fonts, Kit ID `pck6hdf`)
| Family | Variable | Weights | Use |
|--------|----------|---------|-----|
| Source Sans Pro | `--font-sans` | 400, 400i, 700, 700i | Body, buttons, labels |
| Freight Text Pro | `--font-serif` | 400, 400i, 700, 700i | Headings, display text |

### Heading Scale
| Level | Size | Line Height |
|-------|------|-------------|
| `h1` | `clamp(1.9rem, 4.5vw, 2.75rem)` | 1.2 |
| `h2` | `clamp(1.5rem, 3vw, 2.1rem)` | 1.2 |
| `h3` | `clamp(1.2rem, 2.2vw, 1.6rem)` | 1.2 |
| `h4` | 1.15rem | 1.2 |

All headings: `--c-ardoise`, weight 700, letter-spacing `-0.025em`.

### Body
- Font size: 1rem, line-height: 1.8, color: `--c-body-text`
- Links: `--c-ardoise`, underline with 3px offset

### Eyebrow Labels (`.tclas-eyebrow`)
- 0.875rem, weight 700, uppercase, letter-spacing 0.15em
- Variants: `--light` (cream on dark), `--gold`

---

## Buttons

Base class: `.btn` — inline-flex, weight 700, 0.875rem, padding 0.55rem 1.4rem, radius `--radius` (8px).

| Variant | Background | Border | Text | Hover |
|---------|-----------|--------|------|-------|
| `.btn-primary` | `--c-ardoise` | `--c-ardoise` | white | darker blue |
| `.btn-secondary` | transparent | `--c-ardoise` | `--c-ardoise` | fills blue |
| `.btn-danger` | `--c-crimson` | `--c-crimson` | white | darker red |
| `.btn-danger-outline` | transparent | `--c-crimson` | `--c-crimson` | fills red |
| `.btn-outline-light` | transparent | `--c-or-pale` | `--c-or-pale` | fills cream |
| `.btn-ghost` | transparent | none | `--c-ardoise` | underline thickens |

Sizes: `.btn-lg` (1rem), `.btn-sm` (0.875rem).

---

## Layout

| Class | Max Width | Notes |
|-------|-----------|-------|
| `.container-tclas` | 1200px | Standard — NOT Bootstrap's `.container` |
| `.container--narrow` | 780px | Forms, single-column content |

### Section Spacing
| Token | Value |
|-------|-------|
| `--pad-y` | 4rem |
| `--pad-y-lg` | 5.5rem |
| `--pad-y-sm` | 2.5rem |

### Breakpoints (mobile-first, `max-width`)
| Variable | Value |
|----------|-------|
| `$bp-xs` | 480px |
| `$bp-sm` | 600px |
| `$bp-md` | 768px |
| `$bp-lg` | 992px |
| `$bp-xl` | 1200px |

---

## Component Patterns

### Page Header
```html
<div class="tclas-page-header tclas-page-header--ardoise">
  <div class="container-tclas">
    <span class="tclas-eyebrow tclas-eyebrow--light">SECTION</span>
    <h1 class="tclas-page-header__title">Title</h1>
  </div>
</div>
```

### Ruled Decorator (`.tclas-ruled`)
3px crimson bar below element. Variants: `--center`, `--light`.

### Member Badges (`.tclas-badge`)
Inline, 0.75rem, uppercase. Variants: `--neutral`, `--ardoise`, `--vert`, `--crimson`, `--or-pale`.

---

## Logos

- **Header**: WP Customizer (Site Identity)
- **Footer**: `footer_logo` ACF field — light/cream variant for dark background
- **Watermark**: `assets/images/lion-watermark.svg` — body pseudo-element

---

## Style Rules

- **AP style**: "at" not "@", "p.m." not "pm", en-dash for ranges, spell out months
- **Indentation**: tabs only
- **CSS vars**: keep as `:root {}` custom properties, never convert to SASS variables
- **Class prefix**: `.tclas-` with BEM-like modifiers (`--variant`)
- **Accessibility**: visible focus states (gold outline), reduced-motion fallbacks
