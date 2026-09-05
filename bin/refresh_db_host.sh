#!/bin/sh
#
#  Keep the managed database's pinned address honest.
#
#  Run as root from cron, hourly:
#
#      15 * * * * /opt/ggcms/bin/refresh_db_host.sh
#
#  See Docs/Deployment.md under "The database host is pinned" for why the
#  pin exists at all.
#

set -e

HOSTS_FILE="${GGCMS_HOSTS_FILE:-/etc/hosts}"
LOG_TAG="ggcms-refresh-db-host"

		#  How many separate lookups have to agree before we believe a new
		#  address.  This is the whole safety margin of the script.
		#
		#  The pin exists because this droplet loses DNS packets -- six
		#  successes in ten against DigitalOcean's resolver on 5 September
		#  2026.  A script that rewrote the pin on one answer would inherit
		#  exactly the unreliability the pin was added to escape, and would
		#  do it with root privileges on a file that takes seventeen sites
		#  down when it is wrong.  Four agreeing answers, or no change.

AGREEMENTS_REQUIRED=4
LOOKUP_ATTEMPTS=8

log() {
	echo "$LOG_TAG: $1"
	logger -t "$LOG_TAG" "$1" 2>/dev/null || true
}

if [ "$(id -u)" -ne 0 ]; then
	echo "$LOG_TAG: must run as root (it writes $HOSTS_FILE)." >&2
	exit 1
fi

if ! command -v dig >/dev/null 2>&1; then
	log "dig is not installed (apt-get install dnsutils); cannot check the pin"
	exit 1
fi

		#  The hostname and port are PHP's, not ours.  Reading them back out
		#  of the ini means there is one place they are configured, and a
		#  copy of this script on a host with a different database still
		#  does the right thing.

DB_HOST=$(php -r 'echo ini_get("mysqli.default_host");' 2>/dev/null || true)
DB_PORT=$(php -r 'echo ini_get("mysqli.default_port");' 2>/dev/null || true)

if [ -z "$DB_HOST" ]; then
	log "mysqli.default_host is empty; nothing to pin"
	exit 1
fi

case "$DB_HOST" in
	localhost|127.*|::1|/*)
		log "database is local ($DB_HOST); no pin needed"
		exit 0
		;;
esac

[ -n "$DB_PORT" ] || DB_PORT=3306

PINNED=$(awk -v h="$DB_HOST" '$0 !~ /^[ \t]*#/ && $2 == h {print $1; exit}' "$HOSTS_FILE")

		#  dig talks to the resolver directly, which is the point: getent
		#  would read the pin back to us and every run would agree with
		#  itself for ever.

answers=""
attempt=0

while [ "$attempt" -lt "$LOOKUP_ATTEMPTS" ]; do
	attempt=$((attempt + 1))

	one=$(dig +short +time=3 +tries=1 A "$DB_HOST" 2>/dev/null \
		| grep -E '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$' \
		| head -1) || true

	if [ -n "$one" ]; then
		answers="$answers$one
"
	fi
done

if [ -z "$answers" ]; then

		#  Every lookup failed.  That is the normal weather here and it is
		#  precisely when the pin is doing its job, so it is not an error
		#  and must not touch the file.

	log "no successful lookup for $DB_HOST in $LOOKUP_ATTEMPTS attempts; leaving pin at ${PINNED:-none}"
	exit 0
fi

TALLY=$(printf '%s' "$answers" | sort | uniq -c | sort -rn | head -1)
RESOLVED=$(printf '%s' "$TALLY" | awk '{print $2}')
AGREEMENTS=$(printf '%s' "$TALLY" | awk '{print $1}')

if [ "$AGREEMENTS" -lt "$AGREEMENTS_REQUIRED" ]; then
	log "only $AGREEMENTS of $LOOKUP_ATTEMPTS lookups agreed on $RESOLVED; need $AGREEMENTS_REQUIRED, leaving pin at ${PINNED:-none}"
	exit 0
fi

if [ "$RESOLVED" = "$PINNED" ]; then
	log "pin for $DB_HOST still correct at $PINNED"
	exit 0
fi

		#  A different address is the dangerous case, so it is the one that
		#  gets checked rather than trusted.  An address nothing is
		#  listening on is a failed lookup wearing a plausible costume, and
		#  writing it would be worse than writing nothing.
		#
		#  The check goes through PHP rather than /dev/tcp, because this
		#  script runs under dash, where /dev/tcp is not a special file and
		#  the test would quietly pass.  PHP is already a hard dependency,
		#  and fsockopen is what mysqli is about to do anyway.

if ! php -r 'exit(@fsockopen($argv[1], (int) $argv[2], $e, $s, 8) ? 0 : 1);' "$RESOLVED" "$DB_PORT" 2>/dev/null; then
	log "$RESOLVED does not answer on port $DB_PORT; refusing to move the pin from ${PINNED:-none}"
	exit 1
fi

BACKUP="$HOSTS_FILE.ggcms-$(date -u +%Y%m%dT%H%M%SZ)"
cp -a "$HOSTS_FILE" "$BACKUP"

TEMP=$(mktemp "$HOSTS_FILE.ggcms.XXXXXX")

if [ -n "$PINNED" ]; then
	awk -v h="$DB_HOST" -v ip="$RESOLVED" \
		'$0 !~ /^[ \t]*#/ && $2 == h { print ip " " h; next } { print }' \
		"$HOSTS_FILE" > "$TEMP"
else
	cat "$HOSTS_FILE" > "$TEMP"
	printf '%s %s\n' "$RESOLVED" "$DB_HOST" >> "$TEMP"
fi

		#  Same owner and mode as the file we are replacing, then rename,
		#  so a reader never sees a half-written /etc/hosts.

chown --reference="$HOSTS_FILE" "$TEMP"
chmod --reference="$HOSTS_FILE" "$TEMP"
mv "$TEMP" "$HOSTS_FILE"

log "pin for $DB_HOST moved from ${PINNED:-none} to $RESOLVED ($AGREEMENTS/$LOOKUP_ATTEMPTS lookups agreed, port $DB_PORT answered); previous file at $BACKUP"
