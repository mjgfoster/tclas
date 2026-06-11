# TCLAS Development & Deployment Protocol

A solo-developer workflow for a live WordPress site. The whole thing rests on one rule:

> **Code flows UP (dev → staging → prod). Data flows DOWN (prod → staging → dev).**
> The only thing that ever goes up to production is version-controlled code.
> **Never push a database to production** once real members exist — it would
> overwrite their accounts, profiles, and orders with your test data.

This is the big difference from app development with a team: you don't "migrate the
database" by pushing it. Code goes up through git; content and config changes are
made where the data lives (production), or scripted as one-off migrations.

---

## Environments

| Env | Where | Role | Database |
|-----|-------|------|----------|
| **Local (dev)** | Local by Flywheel | Where you build | Disposable — refresh from prod whenever |
| **Staging** | staging5.twincities.lu | Permanent mirror of prod; rehearse every change here | Refreshed *from* prod |
| **Production** | live site (SiteGround) | The real thing | **Source of truth for all member data** |

Keep staging private — HTTP auth + `noindex` — because it holds a copy of real member PII.

---

## What is code vs. data

| Category | Examples | Lives in | Moves how |
|----------|----------|----------|-----------|
| **Code** | clausen theme, `luxembourg-citizenship-quiz` plugin, mu-plugins, **ACF field *definitions*** (registered in PHP in `inc/acf-fields.php`), `.htaccess` | git | Deploys **up** (dev→staging→prod) |
| **Data / config** | page & post content, Privacy/Terms text, members & profiles, PMPro levels/orders, nav menus, plugin settings, ACF field *values*, theme options, Mapbox token | DB | Comes **down** (prod→staging→dev); changes made **in prod** |
| **Per-environment (never synced via git)** | `wp-config.php`, WP core, `wp-content/uploads/` (media), third-party plugins | filesystem / installed per env | Media via WP Synchro or host backups; plugins installed per env |

`wp-config.php` is correctly gitignored — each environment keeps its own (its own DB
creds + keys). Set `WP_ENVIRONMENT_TYPE` in each so you always know where you are.

---

## Branching (main + short feature branches)

- **`main` is always deployable** and matches what's on production.
- Each change gets a short-lived branch off `main`: `feat/...`, `fix/...`, `docs/...`.
- Push the branch to GitHub (`origin`) for backup + the diff view; open a PR if you
  want to review the diff before merging (optional solo, but pleasant).
- Merge to `main` when it's tested and ready.
- **Tag `main` on every production release** (`v1.2`, `v1.3`). Rollback = redeploy the
  previous tag.
- **At launch:** merge `launch/mvp-may-9` into `main`, make `main` the trunk, and retire
  the dated launch branch.

---

## Deploying code (dev → prod) — SiteGround Git push-to-deploy

GitHub (`origin`) stays your source of truth and backup. The SiteGround Git remote is
the deploy pipe. Because only your code is tracked (theme, custom plugin, mu-plugins),
a deploy overlays those files and leaves core, uploads, `wp-config.php`, and
third-party plugins untouched.

**Everyday flow:**
1. Build on a feature branch; push to GitHub.
2. Deploy that branch to **staging5** and test against fresh prod data.
3. Merge to `main`; tag the release.
4. Deploy `main` to **production**.
5. If the change needs a data/config step, do it on prod now (see next section).
6. Smoke-test prod; purge SG Optimizer cache; flush rewrites if routes changed
   (Settings → Permalinks → Save).

**One-time setup** (Site Tools → Devs → Git): create the repo and map deploy targets —
the staging path from your working branch, the production path from `main`. Back up
first and confirm the first deploy leaves `uploads/` and plugins in place. *(Happy to
walk through the exact wiring when you set it up.)*

**Rollback:** redeploy the previous tag. (Data rollback is a separate restore — below.)

---

## Refreshing data DOWN (prod → staging → dev) — WP Synchro

This is how you get realistic member data to develop and rehearse against. Do it
*before* building anything that touches member data.

- Use a WP Synchro **Pull**: prod → staging, and prod → local.
- WP Synchro handles URL search-replace, serialized data, and makes a safety backup.
- **PII caution:** this copies real emails / addresses / ancestry onto your laptop and
  staging. Refresh only as needed, keep local secure, keep staging private. *(If you
  want, I can write a small wp-cli scrub to anonymize emails on the dev copy.)*

**Never run the reverse (push local/staging DB → prod).** The only thing going up is code.

---

## Changing things that live in the DB

Because you can't push a DB up, these changes happen where the data is — **production**:

- **Content** (page text, Privacy/Terms, menus): edit directly in prod. Draft/preview
  in dev first if you like, then re-apply in prod. `LAUNCH_PHASE2_REVERSIONS.md` is the
  pattern — write down exactly what to change, then do it on prod.
- **PMPro levels/prices, plugin settings, theme options:** change in the prod admin.
- **ACF *fields*:** change in code (`inc/acf-fields.php`) → deploys up like any code.
  Their *values* are data.
- **Bulk/structural data changes** (e.g. backfilling a new user-meta default across all
  members): write a **one-off migration** — a `wp-cli eval-file` script or a run-once
  mu-plugin — and run it against prod *after a backup*. Don't hand-edit rows.

---

## Checklists

**Before every prod deploy**
- [ ] Tested on staging (refreshed from prod)
- [ ] Prod DB backup taken (SiteGround on-demand backup)
- [ ] Merged to `main`, release tagged
- [ ] Deployed `main` → prod
- [ ] Any required data/config step applied on prod (migration, menu, price)
- [ ] Smoke-tested; caches purged; rewrites flushed if routes changed

**Risky change (data migration, plugin update, PMPro/Stripe change)**
- [ ] Rehearsed on staging with a fresh prod copy
- [ ] Migration scripted and dry-run on staging
- [ ] Prod DB backup taken immediately before
- [ ] Rollback ready (previous tag + DB backup)
- [ ] Deployed in a low-traffic window

---

## Housekeeping

- `sgs_encrypt_key.php` is currently committed — that's SiteGround Security's key.
  Consider gitignoring it and keeping it per-environment; secrets are better kept out
  of the repo.
- Third-party plugins aren't in git — track their versions and **test plugin updates on
  staging first** before applying to prod.
