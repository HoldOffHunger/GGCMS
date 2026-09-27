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
		#
		#  Excluded as src/data, with no trailing slash.  On the live host it
		#  is a symlink to the data volume -- see Operations.md -- and rsync
		#  reads a trailing slash as "directories only", so src/data/ would
		#  stop matching the link and --delete would remove it.

		#  src/templates/ is excluded from the --delete pass and synced
		#  below instead.  The engine repository carries only the default
		#  template set; every site's own templates live in the private
		#  configuration repository, and a --delete here would remove them
		#  from the host on the first deploy after they moved.
		#
		#  Excluding rather than resyncing afterwards is deliberate.  It
		#  means a host with no configuration checkout leaves the templates
		#  it already has alone, instead of deleting them and restoring
		#  them a moment later -- or, if the checkout is missing, deleting
		#  them and stopping there.

rsync -a --delete --exclude 'src/data' --exclude 'src/templates/' "$REPO/usr/lib/ggcms/" /usr/lib/ggcms/

echo "==> templates -> /usr/lib/ggcms/src/templates/"

		#  The default set is wholly the engine's, so it gets --delete
		#  scoped to itself.

rsync -a --delete "$REPO/usr/lib/ggcms/src/templates/default/" /usr/lib/ggcms/src/templates/default/

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

		#  The two top-level files, which are configuration in their own
		#  right rather than directories of it.  Without this the repository
		#  can hold an edit to apache2.conf that never reaches the host --
		#  which is exactly what happened to the global ServerName.
		#
		#  Named one at a time rather than syncing etc/apache2/ wholesale.
		#  That directory also contains the -enabled symlink farms, and
		#  rsyncing symlinks out of a git checkout leaves Apache reading
		#  nothing.  Adding a file here should be a deliberate act.
		#
		#  envvars and magic are captured in the repository for reference
		#  but deliberately not synced.  envvars is sourced by apache2ctl
		#  itself and sets the user Apache runs as; getting it wrong stops
		#  the server rather than misconfiguring it, and it has never needed
		#  to change.

	for toplevel in apache2.conf ports.conf; do
		if [ -f "$CONFIG/etc/apache2/$toplevel" ]; then
			rsync -a "$CONFIG/etc/apache2/$toplevel" "/etc/apache2/$toplevel"
		fi
	done

		#  Site configuration and site templates, which live here rather
		#  than in the public engine repository because they describe real
		#  sites: per-domain overrides, and the com.<site>.php files that
		#  hold database credentials.
		#
		#  No --delete on either.  A host may carry a domain this checkout
		#  does not know about, and deleting a site's config or its
		#  templates would take that site down with nothing to restore
		#  from but this repository's history.

	if [ -d "$CONFIG/etc/ggcms" ]; then
		echo "==> site config   -> /etc/ggcms/"
		rsync -a "$CONFIG/etc/ggcms/" /etc/ggcms/
	fi

	if [ -d "$CONFIG/usr/lib/ggcms/src/templates" ]; then
		echo "==> site templates -> /usr/lib/ggcms/src/templates/"
		rsync -a "$CONFIG/usr/lib/ggcms/src/templates/" /usr/lib/ggcms/src/templates/
	fi
else
	echo "==> server config -- no checkout at $CONFIG, skipping"
fi

		#  Generated-document cache.  Created if absent; never deleted.  A
		#  symlink to the data volume counts as present.

echo "==> generated document cache"
mkdir -p /usr/lib/ggcms/src/data
chown www-data /usr/lib/ggcms/src/data

		#  git does not preserve an execute bit that was never committed,
		#  and without one even root cannot run these.

echo "==> permissions"
chmod +x /usr/lib/ggcms/cli/scripts/*/*/*.php
chown -R www-data /usr/lib/ggcms /etc/ggcms /var/www
chmod 755 /var/www

		#  restart, not reload.  opcache revalidates each file independently
		#  every two seconds, and rsync writes them one at a time, so for a
		#  moment after a deploy a new file can be compiled against an old one.
		#  On 3 September 2026 that was a new entry-navigation.php calling a
		#  method that only existed in the new SimpleORM.php, which was still
		#  the old one in the cache: "Call to undefined method
		#  view::EntrySiblingURL()", on live pages, after a deploy that
		#  reported success.
		#
		#  A reload does not clear opcache.  A restart does, and it costs a
		#  second of connections that nginx is holding anyway.

echo "==> restarting apache"
apache2ctl configtest
systemctl restart apache2

		#  nginx sits in front of Apache and owns 80 and 443; Apache serves
		#  every render on 8443, unchanged but for its port number.  The
		#  header of ggcms.conf says why.
		#
		#  This runs AFTER the Apache reload deliberately.  Reloaded first,
		#  nginx would try to bind 80 and 443 while Apache still held them.
		#
		#  sites-available only, as with Apache -- the -enabled symlink is
		#  made once by hand, so a deploy can never silently enable or
		#  disable a front end.

if [ -d "$CONFIG/etc/nginx/sites-available" ]; then
	echo "==> nginx config -> /etc/nginx/"

	rsync -a "$CONFIG/etc/nginx/sites-available/" "/etc/nginx/sites-available/"

		#  nginx.conf too, when the configuration repository carries one.
		#
		#  It did not, until 5 September 2026, and that cost a full front-end
		#  outage: open_file_cache was raised past the 1024 descriptors a
		#  worker is given, accept4() began failing with "Too many open files",
		#  and nginx stopped answering on every site while systemd still
		#  reported it active.  The fix -- worker_rlimit_nofile -- belongs in
		#  the main context, which no file under sites-available can reach, so
		#  it was applied by hand to a file nothing tracked.  A package upgrade
		#  would have reverted it silently and returned the outage with no diff
		#  anywhere to explain it.
		#
		#  Optional on purpose: an installation that has not adopted the file
		#  keeps whatever its distribution shipped, and this stays a no-op.

	if [ -f "$CONFIG/etc/nginx/nginx.conf" ]; then
		rsync -a "$CONFIG/etc/nginx/nginx.conf" "/etc/nginx/nginx.conf"
	fi

		#  Never reload a front end that would not start.  nginx -t validates
		#  without touching the running server, so a failed test here leaves
		#  the previous configuration serving rather than nothing at all.

	if nginx -t 2>/dev/null; then
		systemctl reload nginx
	else
		echo "deploy: nginx config did not validate; left the running one alone" >&2
		nginx -t
		exit 1
	fi
fi


echo "==> deployed $(git rev-parse --short HEAD)"
