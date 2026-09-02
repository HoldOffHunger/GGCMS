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

		#  Server configuration, from the second repository.
		#
		#  GreenGluonCMS_Unhireable holds what lives outside the engine --
		#  Apache's vhosts and confs, and anything else the host needs that is
		#  not GGCMS itself.  It is separate because it is private: this
		#  repository is public and server configuration is not.
		#
		#  Skipped rather than fatal when the checkout is absent, so a host
		#  that has not got it yet still deploys the engine.  Clone it with
		#
		#      cd /opt && git clone <url> ggcms-config
		#
		#  No --delete, ever, anywhere under /etc/apache2.  That tree holds
		#  Apache's own shipped configuration, the certbot-managed vhosts, and
		#  everything not yet copied into the repository.  Deleting what is not
		#  in the repo would take the sites off the internet.
		#
		#  Only the -available directories are synced.  The -enabled ones are
		#  symlink farms managed by a2enconf and a2ensite; a symlink through
		#  rsync-from-git fails quietly and leaves Apache reading nothing.
		#  Enabling stays a deliberate one-time act on the host.

CONFIG="${GGCMS_CONFIG_REPO:-/opt/ggcms-config}"

if [ -d "$CONFIG/.git" ]; then
	echo "==> server config -> /etc/apache2/"

	( cd "$CONFIG" && git pull --ff-only )

	for available in conf-available sites-available mods-available; do
		if [ -d "$CONFIG/etc/apache2/$available" ]; then
			rsync -a "$CONFIG/etc/apache2/$available/" "/etc/apache2/$available/"
		fi
	done
else
	echo "==> server config -- no checkout at $CONFIG, skipping"
fi

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

echo "==> reloading apache"
apache2ctl configtest
systemctl reload apache2

echo "==> deployed $(git rev-parse --short HEAD)"
