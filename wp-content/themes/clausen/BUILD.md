# Clausen theme — CSS build status

## ⚠️ The SCSS build is frozen. `assets/css/main.css` is the source of truth.

**Do not run `npm run sass`.** It is intentionally blocked (it errors out). The
compiled CSS and the SCSS sources have **forked**, and rebuilding from SCSS would
revert the live site to an older design and drop shipped styles.

### What "forked" means here

`assets/css/main.css` (~9,900 lines) is what production serves. It has been edited
**directly** over time and is ~1,300 lines ahead of what the SCSS in
`assets/scss/` compiles to. Concretely, compared to a fresh SCSS build:

- **~325 selector blocks exist only in `main.css`** — e.g. the citizenship quiz
  plugin styling (`#lcq-quiz-container`, `.cq_*`), member-page headers
  (`.is-member-page …`), newsletter archive (`.tclas-archive-*`), newer buttons
  (`.btn-solid-ardoise`, `.btn:hover`), and the `--text-*` type-scale tokens and
  `--c-gold-focus` custom properties.
- **~82 selector blocks exist only in the SCSS** — dead code from a *previous*
  hero design (`.tclas-hero__bg`, `.tclas-hero__content`, `.tclas-hero__greeting…`)
  and renamed/removed buttons (`.btn-secondary`, `.btn-danger-outline`).

So the SCSS is simultaneously **behind** (missing shipped styles) and **stale**
(carrying removed designs). `npm run sass` would apply both regressions at once.

Also note: `assets/css/tclas-ancestor-map.css` has the *opposite* drift — its SCSS
has uncompiled edits (a few `font-size` additions) never built into the CSS.

## How to make CSS changes right now

Edit `assets/css/main.css` **directly**. Keep the existing formatting (expanded,
2-space indent). Mirror the change into the matching `assets/scss/partials/*.scss`
when it's easy, for the eventual reconciliation — but the CSS is authoritative.

## Escape hatch (reconciliation only)

The real Sass commands are preserved with an `:unsafe` suffix:

```
npm run sass:unsafe          # build expanded → assets/css
npm run sass:watch:unsafe    # watch
npm run sass:build:unsafe    # build compressed
```

Only use these during a deliberate SCSS reconciliation (below), never for a normal
edit.

## TODO: reconcile SCSS ↔ CSS (post-launch)

Goal: make `npm run sass:unsafe` reproduce the current `main.css` semantically, then
un-block the scripts.

1. Diff a fresh build against the shipped CSS:
   `npm run sass:unsafe` then `git diff assets/css/main.css` (revert after).
2. Port the ~325 compiled-only selector blocks into the appropriate
   `assets/scss/partials/*.scss` (quiz, member header, archive, buttons, tokens).
3. Delete the ~82 stale SCSS-only rules (old hero design, removed buttons).
4. Reconcile value differences (e.g. `var(--text-md)` vs literal `0.875rem`;
   `--c-body-bg: #FAF9F6` vs `#fff`).
5. Rebuild and confirm a normalized diff against the pre-reconciliation `main.css`
   is empty (or only intended changes).
6. Also reconcile `tclas-ancestor-map.scss` → `.css`.
7. Restore the real `sass` / `sass:watch` / `sass:build` scripts in `package.json`
   and delete this warning.
