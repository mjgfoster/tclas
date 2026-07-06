#!/usr/bin/env bash
#
# TCLAS migration runner — execute a one-off migration script via wp-cli.
#
#   bin/migrate.sh bin/migrations/2026-07-06-foo.php          run against LOCAL
#   bin/migrate.sh --prod bin/migrations/2026-07-06-foo.php   run against PROD
#
# Migrations live in bin/migrations/, named YYYY-MM-DD-slug.php, and must be
# idempotent (safe to run twice — check state before changing it). They run
# with WordPress fully loaded via `wp eval-file`. See DEVELOPMENT.md.
#
# Prod runs copy the file up over SSH, execute it, and remove it. Take a
# SiteGround on-demand backup before running a migration against prod.

set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

TARGET="local"
FILE=""
for arg in "$@"; do
	case "$arg" in
		--prod) TARGET="prod" ;;
		*)      FILE="$arg" ;;
	esac
done

[[ -n "$FILE" && -f "$FILE" ]] || { echo "Usage: bin/migrate.sh [--prod] bin/migrations/<file>.php" >&2; exit 1; }

if [[ "$TARGET" == "local" ]]; then
	# Prefer the ~/tclas-wp wrapper (handles Local's MySQL socket etc.)
	WP="${HOME}/tclas-wp"
	[[ -x "$WP" ]] || WP="wp"
	echo "== Running $(basename "$FILE") against LOCAL"
	"$WP" eval-file "$FILE"
else
	CONF="bin/deploy.conf"
	[[ -f "$CONF" ]] || { echo "ERROR: $CONF not found (see bin/deploy.conf.example)." >&2; exit 1; }
	# shellcheck source=/dev/null
	source "$CONF"
	: "${REMOTE:?}" "${REMOTE_PATH:?}"
	REMOTE_TMP=".migration-$(basename "$FILE")"
	echo "== Running $(basename "$FILE") against PROD ($REMOTE)"
	echo "   (have you taken a SiteGround on-demand backup?)"
	scp -q "$FILE" "$REMOTE:$REMOTE_PATH/$REMOTE_TMP"
	ssh "$REMOTE" "cd $REMOTE_PATH && wp eval-file $REMOTE_TMP; rm -f $REMOTE_TMP"
fi
