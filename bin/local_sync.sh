#!/bin/bash
#
#  GGCMS on a development machine.
#
#  Lays both checkouts out the way bin/deploy.sh does on the live host --
#  same paths, same Apache and nginx configuration -- so a production fault
#  can be reproduced locally.  Nothing is pulled from git, and nothing public
#  is served:
#
#    nginx   :8000  (production: 80/443)  the site blocks become one localhost block
#    Apache  :8443  (as production)       one local vhost, self-signed certificate
#
#  Browse to http://localhost:8000/?domain=<site>; Handler's LocalHostHandler
#  picks the site.  Run as root.  Rerun after every edit.
#
#  Written for Ubuntu under WSL, where Windows' own web service holds port 80
#  and there is no systemd, hence `service` rather than `systemctl`.
#
#      GGCMS_REPO=/path/to/GGCMS GGCMS_CONFIG_REPO=/path/to/GGCMS_Unhireable bin/local_sync.sh

set -e

REPO="${GGCMS_REPO:-/mnt/e/GGCMS}"
CONFIG="${GGCMS_CONFIG_REPO:-/mnt/e/GreenGluonCMS_Unhireable}"

mkdir -p --mode=755 /etc/ggcms /usr/lib/ggcms /var/log/ggcms /srv/ggcms /var/www/html

echo "==> engine, templates, config, docroot"
rsync -a --delete --exclude 'src/data' --exclude 'src/templates/' "$REPO/usr/lib/ggcms/" /usr/lib/ggcms/
mkdir -p /usr/lib/ggcms/src/templates/default
rsync -a --delete "$REPO/usr/lib/ggcms/src/templates/default/" /usr/lib/ggcms/src/templates/default/
rsync -a "$REPO/etc/ggcms/" /etc/ggcms/
rsync -a "$REPO/var/www/html/" /var/www/html/
cp -a "$REPO/var/www/ggcms_install_directories.php" /var/www/
cp -a "$REPO/var/www/ggcms_cli_directories.php" /var/www/
rsync -a "$CONFIG/etc/ggcms/" /etc/ggcms/
rsync -a "$CONFIG/usr/lib/ggcms/src/templates/" /usr/lib/ggcms/src/templates/

echo "==> apache"
		#  Production's top-level files, confs and module settings.  Not its
		#  sites: every one names a Let's Encrypt certificate this box has not
		#  got.  mods-available is copied but only what is enabled below loads.
rsync -a "$CONFIG/etc/apache2/apache2.conf" "$CONFIG/etc/apache2/ports.conf" /etc/apache2/
rsync -a "$CONFIG/etc/apache2/conf-available/" /etc/apache2/conf-available/
rsync -a "$CONFIG/etc/apache2/mods-available/" /etc/apache2/mods-available/
		#  One vhost in their place, on production's 8443, with Ubuntu's
		#  self-signed snakeoil certificate.  nginx does not verify it, as it
		#  does not in production.
cat > /etc/apache2/sites-available/fumiko-local.conf <<'EOF'
<VirtualHost *:8443>
	ServerName localhost
	DocumentRoot /var/www/html
	SSLEngine on
	SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
	SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
	ErrorLog  ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
a2enmod -q rewrite ssl headers php8.1 >/dev/null
a2ensite -q fumiko-local >/dev/null
a2dissite -q 000-default default-ssl >/dev/null 2>&1 || true

		#  Production's Apache also listens on 8080, and so would nginx on a
		#  host without Windows' own service holding port 80.  Only 8443 here.
sed -i 's/^Listen 8080$/# Listen 8080  (local_sync: unused)/' /etc/apache2/ports.conf

echo "==> nginx"
[ -f "$CONFIG/etc/nginx/nginx.conf" ] && rsync -a "$CONFIG/etc/nginx/nginx.conf" /etc/nginx/nginx.conf

		#  Everything above the first server block -- the cache maps, the bot
		#  rules, the rate limits, the upstream -- verbatim.  Then the first
		#  real site block, made plain-HTTP on 8000 and named localhost.
awk '
	/^server \{/ && !head_done { head_done = 1 }
	!head_done { print; next }
	/^server \{/ { block = ""; inside = 1 }
	inside { block = block $0 "\n" }
	inside && /^\}/ {
		inside = 0
		if (block ~ /location @engine/ && !printed) { printf "%s", block; printed = 1 }
	}
' "$CONFIG/etc/nginx/sites-available/ggcms.conf" > /tmp/ggcms-local.conf

site=$(grep -m1 -oP '^\s*proxy_ssl_name\s+\K[^;]+' /tmp/ggcms-local.conf)
sed -i \
	-e '/^\s*listen /d' \
	-e 's/^\(\s*\)server_name .*;/\1listen 8000 default_server;\n\1server_name localhost;/' \
	-e '/ssl_certificate/d' \
	-e "s/$site/localhost/g" \
	/tmp/ggcms-local.conf
mv /tmp/ggcms-local.conf /etc/nginx/sites-available/ggcms-local.conf
ln -sf /etc/nginx/sites-available/ggcms-local.conf /etc/nginx/sites-enabled/ggcms-local.conf
rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-enabled/ggcms.conf

echo "==> permissions"
mkdir -p /usr/lib/ggcms/src/data
chmod +x /usr/lib/ggcms/cli/scripts/*/*/*.php
chown -R www-data /usr/lib/ggcms /etc/ggcms /var/log/ggcms /srv/ggcms /var/www
chmod 755 /var/www /var/log/ggcms

echo "==> restarting"
		#  restart, not reload: see deploy.sh on opcache.
apache2ctl configtest
service apache2 restart
nginx -t
service nginx restart
service mysql status >/dev/null 2>&1 || service mysql start
echo "==> done"
