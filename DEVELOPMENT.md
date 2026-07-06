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
| **Staging** | Site Tools → WordPress → Staging (ephemeral copies) | Rehearse risky changes, then delete | Fresh prod clone each time |
| **Production** | live site (SiteGround) | The real thing | **Source of truth for all member data** |

Staging copies are created fresh from prod per rehearsal and deleted after — no
permanent mirror to drift or to hold member PII long-term. *(staging5.twincities.lu,
the standalone site used to launch, is retired from the workflow.)*

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

## Deploying code (dev → prod) — `bin/deploy.sh` (rsync over SSH)

GitHub (`origin`) stays your source of truth and backup. Deploys are an rsync of the
**committed** tree (via `git archive` — never the working tree, so uncommitted edits
and untracked local files like the mail-guard mu-plugin can never ship) to prod over
plain SSH. Only the tracked code paths move — theme, custom plugin, mu-plugins — and
core, uploads, `wp-config.php`, `.htaccess` (SG Optimizer owns prod's copy), and
third-party plugins are never touched.

```
bin/deploy.sh              # dry run — shows exactly what would change
bin/deploy.sh --go         # deploy HEAD
bin/deploy.sh --go v1.2    # deploy a tag  →  rollback = deploy the previous tag
```

**Everyday flow:**
1. Build on a feature branch; push to GitHub.
2. Rehearse on staging if the change is risky (see Staging below).
3. Merge to `main`; tag the release.
4. `bin/deploy.sh` (check the dry run), then `bin/deploy.sh --go`.
5. If the change needs a DB/config step, run its **migration** now (next section).
6. Smoke-test prod; the script purges SG Optimizer's cache; flush rewrites if
   routes changed (Settings → Permalinks → Save).

**One-time setup:**
1. Site Tools → Devs → **SSH Keys Manager** → generate (or import) a key; note the
   hostname, username, and port (usually 18765).
2. Add a host alias to `~/.ssh/config` (see `bin/deploy.conf.example`).
3. `cp bin/deploy.conf.example bin/deploy.conf` and fill in the alias + WP root path.
   (`deploy.conf` is gitignored — it's per-machine.)
4. First deploy: run the dry run and confirm it only lists theme/plugin/mu-plugin
   files before `--go`.

**Rollback:** `bin/deploy.sh --go <previous-tag>`. (Data rollback is a separate
restore — below.)

---

## Staging — SiteGround's built-in staging (ephemeral, not a pet)

GrowBig includes one-click staging: **Site Tools → WordPress → Staging**. Use it as a
disposable rehearsal space, not a standing mirror — a permanent staging site drifts
and holds a copy of member PII around the clock.

**When to rehearse on staging:** plugin updates (PMPro / Stripe / TEC especially),
migrations, permalink/routing changes, anything touching checkout or member data.
CSS and template-text changes can go local → prod directly.

**Flow:** create a fresh staging copy (it clones current prod, data included) →
deploy the feature branch to it / run the migration against it → click through →
delete the copy (or use push-to-live only if you fully understand what it replaces —
deploying to prod via `bin/deploy.sh` is the normal route).

Staging copies hold real member PII: keep them short-lived and don't hand out the URL.

---

## Refreshing data DOWN (prod → dev) — WP Synchro

This is how you get realistic member data to develop against. Make it routine: pull
**before starting any feature work**, not just when local feels stale — most "does
prod match local?" uncertainty comes from skipping this.

- Use a WP Synchro **Pull**: prod → local.
- WP Synchro handles URL search-replace, serialized data, and makes a safety backup.
- The mail-guard mu-plugin (`zzz-local-email-guard.php`, untracked) must stay in
  place locally — a pull brings prod's live Brevo keys with it.
- **PII caution:** this copies real emails / addresses / ancestry onto your laptop.
  Refresh only as needed and keep local secure. *(A wp-cli scrub to anonymize emails
  on the dev copy is a good future addition.)*

**Never run the reverse (push local DB → prod).** The only thing going up is code.

---

## Changing things that live in the DB

Because you can't push a DB up, these changes happen where the data is — **production**:

- **One-off prose edits** (Privacy/Terms text, a menu item): edit directly in prod
  admin. Draft/preview in dev first if you like, then re-apply in prod.
- **PMPro levels/prices, plugin settings, theme options:** change in the prod admin.
- **ACF *fields*:** change in code (`inc/acf-fields.php`) → deploys up like any code.
  Their *values* are data.
- **Anything a code change depends on, and any bulk/structural change: write a
  migration.** Migrations live in `bin/migrations/YYYY-MM-DD-slug.php`, run via
  `bin/migrate.sh` (locally) and `bin/migrate.sh --prod` (over SSH), and must be
  **idempotent** — verify state before changing it, log every action, leave anything
  unexpected alone with a warning. Test against local first (where the change is
  usually already applied — a clean all-"OK" run is your idempotency proof), then run
  against prod right after the code deploy. Don't hand-type checklists into prod.
  `bin/migrations/2026-07-06-launch-audit-cleanup.php` is the reference example.

---

## Checklists

**Before every prod deploy**
- [ ] Prod DB backup taken (SiteGround on-demand backup)
- [ ] Merged to `main`, release tagged
- [ ] `bin/deploy.sh` dry run reviewed, then `bin/deploy.sh --go`
- [ ] Any companion migration run: `bin/migrate.sh --prod bin/migrations/<file>.php`
- [ ] Smoke-tested; rewrites flushed if routes changed (cache purge is automatic)

**Risky change (data migration, plugin update, PMPro/Stripe change)**
- [ ] Rehearsed on a fresh Site Tools staging copy (deploy + migration both)
- [ ] Migration tested on local first (idempotent, all-"OK" on re-run)
- [ ] Prod DB backup taken immediately before
- [ ] Rollback ready (previous tag + DB backup)
- [ ] Deployed in a low-traffic window; staging copy deleted after

---

## Housekeeping

- `sgs_encrypt_key.php` is currently committed — that's SiteGround Security's key.
  Consider gitignoring it and keeping it per-environment; secrets are better kept out
  of the repo.
- Third-party plugins aren't in git — track their versions and **test plugin updates on
  staging first** before applying to prod.
