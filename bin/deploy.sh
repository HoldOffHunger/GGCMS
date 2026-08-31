#!/bin/sh
#
#  GGCMS deployment.
#
#  Pulls the current main branch and syncs it into place on a live host.
#  Run as root on the server:
#
#      /opt/ggcms/bin/deploy.sh
#
#  See Docs/Deployment.md for first-time setup and for what this does not do.
#

set -e

REPO="${GGCMS_REPO:-/opt/ggcms}"

if [ ! -d "$REPO/.git" ]; then
	echo "deploy: $REPO is not a git checkout.  See Docs/Deployment.md." >&2
	exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
	echo "deploy: must run as root (it writes to /usr/lib, /etc and /var/www)." >&2
	exit 1
fi

		#  Refuse to deploy on a full disk.  A half-written sync is worse
		#  than no sync, and this host has been here before.

available_kb=$(df -Pk / | awk 'NR==2 {print $4}')

if [ "$available_kb" -lt 524288 ]; then
	echo "deploy: less than 512MB free on /.  Refusing.  See Docs/Operations.md." >&2
	df -h /
	exit 1
fi

		#  Fetch.  --ff-only so a divergent local state fails loudly
		#  rather than producing a merge commit nobody asked for.

echo "==> pulling"
cd "$REPO"
git pull --ff-only

echo "==> engine    -> /usr/lib/ggcms/"

		#  --delete here is deliberate: /usr/lib/ggcms is pure code, and a
		#  file removed from the repository must disappear from the host.
		#
		#  Except that it is not quite pure code.  src/data/ holds generated
		#  documents -- the RTF, TEX, SGML, OPDS and PDF renderings the format
		#  classes cache to disk -- and it is not in the repository.  The first
		#  deploy of this script deleted it, and every document-format request
		#  afterwards failed on fopen() returning false.  Excluded, and
		#  recreated below in case it is missing.

rsync -a --delete --exclude 'src/data/' "$REPO/usr/lib/ggcms/" /usr/lib/ggcms/

echo "==> config    -> /etc/ggcms/"

		#  No --delete.  Sites may hold local configuration that is not
		#  tracked, and losing it silently would be unrecoverable.

rsync -a "$REPO/etc/ggcms/" /etc/ggcms/

echo "==> docroot   -> /var/www/html/"

		#  No --delete.  The page cache lives under the document root and
		#  is not in the repository; deleting it here would be merely
		#  wasteful, but user-dropped files would be gone for good.

rsync -a "$REPO/var/www/html/" /var/www/html/
cp -a "$REPO/var/www/ggcms_install_directories.php" /var/www/
cp -a "$REPO/var/www/ggcms_cli_directories.php" /var/www/

		#  Generated-document cache.  Created if absent; never deleted.

echo "==> generated document cache"
mkdir -p /usr/lib/ggcms/src/data
chown www-data /usr/lib/ggcms/src/data

		#  git does not preserve an execute bit that was never committed,
		#  and without one even root cannot run these.

echo "==> permissions"
chmod +x /usr/lib/ggcms/cli/scripts/*/*/*.php
chown -R www-data /usr/lib/ggcms /etc/ggcms /var/www
chmod 755 /var/www

		#  Code changed, so every cached page is potentially stale.

		#  The flush races live traffic: a request can be writing a new cache
		#  file into a directory while rm is removing it, which surfaces as
		#  "Directory not empty" and, under set -e, aborted the deploy before
		#  Apache was reloaded.  A cache flush must never be able to fail a
		#  deployment -- a stale cache entry is a nuisance, an unreloaded
		#  Apache is an outage waiting to be noticed.
		#
		#  Two passes, both non-fatal: the second catches whatever was created
		#  during the first.

if [ -d /var/www/html/_cache ]; then
	echo "==> flushing page cache"
	rm -rf /var/www/html/_cache/* 2>/dev/null || true
	rm -rf /var/www/html/_cache/* 2>/dev/null || true
fi

echo "==> reloading apache"
apache2ctl configtest
systemctl reload apache2

echo "==> deployed $(git rev-parse --short HEAD)"
