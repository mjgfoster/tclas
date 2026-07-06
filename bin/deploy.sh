#!/usr/bin/env bash
#
# TCLAS deploy — rsync committed code to production over SSH.
#
#   bin/deploy.sh              dry run of HEAD (shows what would change)
#   bin/deploy.sh --go         deploy HEAD for real
#   bin/deploy.sh --go v1.2    deploy a tag/ref (rollback = deploy the previous tag)
#
# What it deploys (and nothing else):
#   wp-content/themes/clausen/
#   wp-content/plugins/luxembourg-citizenship-quiz/
#   wp-content/mu-plugins/  (tracked files only, never deletes remote extras)
#
# Deliberately NOT deployed:
#   .htaccess           — SG Optimizer writes its own rules into prod's copy
#   wp-config.php       — per-environment (gitignored anyway)
#   uploads, core, third-party plugins — never in git
#
# Deploys from `git archive`, NOT the working tree — so uncommitted edits and
# untracked local files (e.g. the zzz-local-email-guard mu-plugin) can never
# reach production.
#
# Setup (one-time): see DEVELOPMENT.md → "Deploying code". Requires
# bin/deploy.conf (gitignored) with REMOTE and REMOTE_PATH.

set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

CONF="bin/deploy.conf"
if [[ ! -f "$CONF" ]]; then
	echo "ERROR: $CONF not found. Copy bin/deploy.conf.example and fill in your SSH details." >&2
	exit 1
fi
# shellcheck source=/dev/null
source "$CONF"
: "${REMOTE:?deploy.conf must set REMOTE (ssh host alias)}"
: "${REMOTE_PATH:?deploy.conf must set REMOTE_PATH (WP root on the server)}"

GO=0
REF="HEAD"
for arg in "$@"; do
	case "$arg" in
		--go) GO=1 ;;
		*)    REF="$arg" ;;
	esac
done

git rev-parse --verify --quiet "$REF" > /dev/null || { echo "ERROR: unknown ref '$REF'" >&2; exit 1; }
SHA=$(git rev-parse --short "$REF")

# Warn when deploying HEAD with uncommitted changes in deploy paths — the
# archive won't include them, which is usually not what you meant.
if [[ "$REF" == "HEAD" ]] && ! git diff --quiet HEAD -- wp-content/themes/clausen wp-content/plugins/luxembourg-citizenship-quiz wp-content/mu-plugins; then
	echo "WARNING: uncommitted changes in deploy paths — they will NOT be deployed." >&2
fi

# Export the committed tree to a temp dir and rsync from there.
STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT
git archive "$REF" | tar -x -C "$STAGE"

RSYNC_FLAGS=(-rlptvz --checksum)
[[ $GO -eq 1 ]] || RSYNC_FLAGS+=(--dry-run)

MODE_LABEL=$([[ $GO -eq 1 ]] && echo "DEPLOYING" || echo "DRY RUN (pass --go to deploy)")
echo "== $MODE_LABEL: $REF ($SHA) → $REMOTE:$REMOTE_PATH"

# Theme + custom plugin: exact mirrors of git (--delete prunes removed files).
for path in wp-content/themes/clausen wp-content/plugins/luxembourg-citizenship-quiz; do
	echo "-- $path/"
	rsync "${RSYNC_FLAGS[@]}" --delete \
		"$STAGE/$path/" "$REMOTE:$REMOTE_PATH/$path/"
done

# mu-plugins: push tracked files only; NO --delete (prod may legitimately hold
# mu-plugins that aren't in git, e.g. host- or plugin-installed ones).
echo "-- wp-content/mu-plugins/ (no delete)"
rsync "${RSYNC_FLAGS[@]}" \
	"$STAGE/wp-content/mu-plugins/" "$REMOTE:$REMOTE_PATH/wp-content/mu-plugins/"

if [[ $GO -eq 1 ]]; then
	echo "== Purging SG Optimizer cache"
	ssh "$REMOTE" "cd $REMOTE_PATH && wp sg purge" || echo "WARNING: cache purge failed — purge manually in Site Tools." >&2
	echo "== Deployed $SHA."
	echo "   Reminders: run any pending migration (bin/migrate.sh --prod <file>);"
	echo "   flush permalinks in wp-admin if routes changed; tag the release."
else
	echo "== Dry run complete — nothing was changed."
fi
