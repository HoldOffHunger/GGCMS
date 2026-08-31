# Operations

Routine maintenance for a live GGCMS host.

This document exists because on 30 August 2026 the production droplet was found
with a full disk, an HTTPS certificate that had been expired for thirteen
months, and eighteen sites serving browser security warnings. None of those
were hard problems. Every one of them was a problem nobody was told about.

## The failure that motivated this document

The chain, in order:

1. UFW logged every blocked packet to the kernel log. Port scans against a
   public IP are constant, so `kern.log` grew without limit. It reached 3.4 GB,
   with another 3.1 GB in the systemd journal and 6.6 GB in Apache's access
   logs — roughly 13.6 GB of logs on a 25 GB disk.
2. The disk filled.
3. `certbot renew` runs twice daily from a systemd timer. With no writable
   temp directory it could not even create its working directory, and died on
   `FileNotFoundError` before reaching any certificate. It had been failing
   this way, silently, every twelve hours for over two years.
4. The certificate expired on 19 July 2024. Every site began showing a
   full-page browser interstitial.
5. PHP could not write, MySQL could not spill temp tables, and page renders
   that should take milliseconds took eight to twenty seconds.

The tool that would have caught step 2 already existed —
`cli/scripts/public/storage/check_free_space.php` — and had existed since 2022.
It was never scheduled. **Diagnostics that are not on a timer do not exist.**

## Caps that must be in place

### Firewall logging

UFW's per-packet logging is the single largest source of kernel log growth and
its value on a public host is near zero.

```bash
ufw logging off
```

Use `ufw logging low` instead if you want blocked-packet records, but only with
the journald cap below in place.

### systemd journal

The journal has no size limit by default. In `/etc/systemd/journald.conf`:

```ini
[Journal]
SystemMaxUse=200M
SystemMaxFileSize=50M
MaxRetentionSec=1month
```

Then `systemctl restart systemd-journald`.

### Apache access and error logs

Apache's logs are the largest single directory on a busy host. Confirm
`/etc/logrotate.d/apache2` exists and is being run, and that rotation is
capped by size as well as by age:

```
/var/log/apache2/*.log {
        daily
        rotate 14
        maxsize 100M
        missingok
        notifempty
        compress
        delaycompress
        sharedscripts
        postrotate
                if invoke-rc.d apache2 status > /dev/null 2>&1; then
                        invoke-rc.d apache2 reload > /dev/null 2>&1
                fi
        endscript
}
```

Verify rotation is actually happening — a stale `logrotate` timer is invisible
until the disk is full:

```bash
systemctl status logrotate.timer && logrotate -d /etc/logrotate.conf 2>&1 | tail -30
```

### Kernel and auth logs

`kern.log`, `auth.log` and `btmp` are covered by `/etc/logrotate.d/rsyslog`.
`btmp` records failed logins and grows under SSH brute force; 71 MB of it was
present at the time of the incident.

## Recovering a full disk

Find the weight before deleting anything:

```bash
df -h /; df -i /; du -xh --max-depth=2 / 2>/dev/null | sort -h | tail -30
```

`df -i` matters as much as `df -h`. "No space left on device" is also what
Linux reports when inodes are exhausted, which needs the opposite fix — many
small files rather than a few large ones.

Safe to remove, in order of return:

```bash
journalctl --vacuum-size=200M
rm -f /var/log/kern.log.* /var/log/btmp.1 /var/log/ufw.log.*
truncate -s 0 /var/log/kern.log /var/log/btmp /var/log/ufw.log
apt-get clean
rm -rf /var/crash/* /root/.cache
```

Use `truncate -s 0` rather than `rm` on any file a running process still holds
open. Deleting such a file does not return its space until the process
restarts; truncating returns it immediately.

**Never** clear `/srv/ggcms/`. That is site content — uploaded images and
per-site payload — and it is not in the code repository.

## Packages that quietly take a URL path

Installed 2023-10-29, found 2026-08-31. For nearly three years, **every
JavaScript file on all seventeen sites returned 404** — tooltips, clickable
rows, like/dislike and the audio controls were inert — and nothing in this
codebase was at fault.

The Debian package `javascript-common` ships an Apache configuration:

```apache
Alias /javascript /usr/share/javascript/
```

An `Alias` is resolved before `.htaccess`, before the front controller, before
anything here gets a say. Every request for `/javascript/anything.js` was sent
to Debian's own JavaScript tree, which does not contain these files, and
Apache returned its own 404 without the CMS ever being started. The files were
present, readable, and unreachable.

It arrives as a dependency of `libjs-jquery` and `libjs-jquery-ui`, and its
postinst runs `a2enconf`, so **it re-enables itself on every install and
upgrade**. Disabling the conf alone is not durable.

The durable form is three layers:

```bash
apt-get purge -y javascript-common
dpkg-divert --local --rename --add /etc/apache2/conf-available/javascript-common.conf
printf 'Package: javascript-common\nPin: release *\nPin-Priority: -1\n' > /etc/apt/preferences.d/no-javascript-common
```

The purge removes it, the divert claims the conf path so a forced reinstall
cannot put the file back where Apache reads it, and the pin makes apt refuse
the package outright — including as a dependency, which will look like a
broken install one day and is the intended behaviour.

The general lesson is worth more than the specific package. **A distribution
package can claim a URL path on this host, and it will do so silently.** When
static files that demonstrably exist return Apache's own 404 rather than the
CMS's, read `/etc/apache2/conf-enabled/` before reading any code. Note that
`grep -r` does not follow the symlinks in that directory; use `grep -rR` or
read `conf-available/` directly, or the offending line will not appear.

## Certificates

Certificates are issued by Let's Encrypt through certbot and last 90 days.
Renewal is automatic via `certbot.timer`; the timer is reliable, and every
observed failure has been an environmental one underneath it.

```bash
certbot certificates                       # expiry for every cert
systemctl status certbot.timer             # is renewal even scheduled
certbot renew                              # renew everything eligible
certbot renew --dry-run                     # rehearse without spending quota
```

`certbot renew` reuses the authenticator stored at issue time. These hosts were
issued with `--apache`, so Apache must be **running** during renewal. The
stop-Apache sequence in the older install notes applies only to `--standalone`.

Renewal failures for a domain whose DNS no longer points at this host are
expected and harmless; certbot renews what it can and reports the rest.

## Monitoring — what must be scheduled

GGCMS ships its own diagnostics under `usr/lib/ggcms/cli/`. They only help if
they run. Install as root's crontab:

```cron
# Disk and inode headroom — the check that would have prevented the 2024 outage
0 6 * * *   /usr/lib/ggcms/cli/scripts/public/storage/check_free_space.php

# Database size growth
0 6 * * 1   /usr/lib/ggcms/cli/scripts/public/sql/show_table_sizes.php

# Application errors and 404 volume from the previous day
0 7 * * *   /usr/lib/ggcms/cli/scripts/internal/errors/server_error_counts.php
0 7 * * *   /usr/lib/ggcms/cli/scripts/internal/errors/issues_404.php

# Database backup
0 3 * * *   /usr/lib/ggcms/cli/scripts/public/sql/backup_database.php

# Schema drift, and child tables holding rows nothing is allowed to fetch
0 6 * * 1   /usr/lib/ggcms/cli/scripts/public/sql/check_schema.php

# Keep the busiest pages warm on the two heaviest sites
30 4 * * *  /usr/lib/ggcms/cli/scripts/public/cache/warm_cache.php --domain=revoltlib.com --quiet
40 4 * * *  /usr/lib/ggcms/cli/scripts/public/cache/warm_cache.php --domain=earthfluent.com --quiet
```

Cron mails its output to root. `You have new mail` at login is a signal, not
decoration — it was present, unread, throughout the outage.

A crude external check is worth more than any of the above, because it survives
the host being wholly unresponsive. From any machine that is not this one:

```bash
curl -sS -o /dev/null -w '%{http_code} %{time_total}s\n' https://revoltlib.com/
```

Anything other than `200` in under a second wants investigating. An expired
certificate returns no HTTP code at all.

## Decommissioning a site

Removing a domain touches five places. Missing any one of them leaves either a
broken vhost, an orphaned renewal that fails forever, or content on disk that
nobody remembers paying for.

Using `abstractcon.com` as the worked example:

```bash
# 1. Take the vhost out of Apache and reload
a2dissite abstractcon.com.conf abstractcon.com-le-ssl.conf
systemctl reload apache2

# 2. Retire the certificate, so certbot stops trying to renew it forever
certbot delete --cert-name abstractcon.com

# 3. Archive the site's content before removing it
tar czf /root/abstractcon.com-content-$(date +%F).tar.gz /srv/ggcms/abstractcon.com/
# copy that archive off the host, verify it, and only then:
rm -rf /srv/ggcms/abstractcon.com/

# 4. Archive and remove logs and configuration
tar czf /root/abstractcon.com-logs-$(date +%F).tar.gz /var/log/ggcms/abstractcon.com/
rm -rf /var/log/ggcms/abstractcon.com/ /etc/ggcms/abstractcon.com/
rm -f /etc/apache2/sites-available/abstractcon.com*.conf

# 5. Dump and drop the database, if the site had its own
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p abstractcon > /root/abstractcon-$(date +%F).sql
```

Then remove the site's template directory from this repository
(`usr/lib/ggcms/src/templates/<site>/`) and delete the DNS records:

```bash
doctl compute domain records list abstractcon.com
doctl compute domain delete abstractcon.com
```

Archive first, verify the archive opens, and only then delete. There is no
undo on any of these steps.
