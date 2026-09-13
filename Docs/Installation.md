# Installation

Standing up a GGCMS host from bare Linux.

Derived from the author's own handover notes, with credentials removed. The
reference host is a DigitalOcean LAMP droplet running Ubuntu with Apache and
PHP 8.x.

> **Nothing in this document should ever contain a password.** Database
> credentials live in `php.ini`, which is gitignored for exactly that reason.
> If you find yourself pasting a real credential into a file in this
> repository, stop.

## Checking your work

Every requirement below is verified by one command, which reads and changes
nothing:

```bash
/usr/lib/ggcms/cli/scripts/public/install/check_install.php
```

Run it before you start, to see what is missing, and again at the end. Each
failure names the section here that fixes it.

## PHP extensions

Find the active configuration first, because the CLI and Apache SAPIs load
different files and editing the wrong one is a long afternoon:

```bash
php -i | grep -i php.ini
```

Then install what the engine needs:

```bash
apt-get install php-mbstring php-mysql php-curl php-pear php-dev
apt-get install php8.1-xml php8.1-zip php8.1-intl
apt-get install imagemagick php-imagick
```

`mbstring` is not optional — the engine is UTF-8 throughout and calls `mb_*`
functions directly. `xml` provides `DOMDocument`, which the CLI `SSL` trait
uses to parse Apache vhosts. `zip` provides `ZipArchive` for the EPub format.
`intl` provides `normalizer_normalize()`, which `SimpleForms` uses to turn a
title into a URL code — so without it no entry can be saved.

Restart Apache after each group.

### The CLI and Apache are different PHPs

Installing these gets them into Apache. **It does not follow that the `php` on
root's `$PATH` has them**, and on this host it does not: `/usr/bin/php` was
8.3 with neither `mbstring` nor `mysqli`, while `php8.1` had both. Every tool in
`cli/` carries `#!/usr/bin/php`, so all of them were pointing at the wrong
interpreter — which is why the ISE conversion tool shells out to `mysql -e`
rather than using `mysqli`.

```bash
update-alternatives --set php /usr/bin/php8.1
```

Check what a given interpreter actually has before concluding a tool is broken:

```bash
php8.1 -m | grep -iE 'mbstring|mysqli|intl|xml|zip'
```

`cli/classes/Entries/EntryModifier.php` checks this set before it constructs
anything, and prints the `apt-get` line rather than letting the failure arrive
as an undefined-function error four frames inside a constructor.

### Composer and Guzzle

Dependencies are **vendored in this repository** and do not need fetching on a
deploy — see the note in [README.md](../README.md). Composer is only needed if
you are adding or updating a dependency:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php && rm composer-setup.php
mv composer.phar /usr/local/bin/composer
```

### One package to remove

```bash
apt-get purge javascript-common
```

This package makes every file under `<docroot>/javascript/` return a 404 —
its own ad-laden 404, not Apache's. It is not obvious and it wastes hours.

## Directory layout

```bash
mkdir --mode=755 /etc/ggcms /usr/lib/ggcms /var/log/ggcms /srv/ggcms
```

| Path | Holds |
|---|---|
| `/usr/lib/ggcms/` | The engine (`src/`, `cli/`, `dep/`, `tests/`) |
| `/etc/ggcms/` | Per-domain configuration |
| `/srv/ggcms/<domain>/www/` | Site content and uploaded images |
| `/var/log/ggcms/<domain>/stats/` | Per-domain logs |
| `/var/www/html/` | Document root: `.htaccess`, `index.php`, shared assets |
| `/mnt/<volume>/ggcms_cache/` | DB file cache and page cache |

Use `755` on directories, not `644`. A directory needs its execute bit to be
traversed at all — this is the answer to the "why does 644 not work?" note in
the original setup file.

```bash
chown -R www-data /usr/lib/ggcms /etc/ggcms /var/log/ggcms /srv/ggcms /var/www
chmod 755 /var/www /var/log/ggcms
```

## Apache

`.htaccess` is load-bearing here — the entire routing model depends on it, so
overrides must be permitted. In `/etc/apache2/apache2.conf`, for the `/var/www`
block:

```apache
AllowOverride All
```

Confirm `AccessFileName .htaccess` is set, then:

```bash
apache2ctl configtest && systemctl restart apache2
```

### MPM sizing

The Ubuntu default is `MaxRequestWorkers 150` and `MaxConnectionsPerChild 0`.
On a 1 vCPU host that permits about a hundred concurrent PHP processes on one
core, which produces load averages above 50 and page times measured in tens of
seconds. Workers also never retire, so leaked memory accumulates indefinitely.

Size it to the hardware. In `/etc/apache2/mods-available/mpm_prefork.conf`:

```apache
<IfModule mpm_prefork_module>
	StartServers			 3
	MinSpareServers		  3
	MaxSpareServers		  8
	MaxRequestWorkers	   20
	MaxConnectionsPerChild  500
</IfModule>
```

Fewer workers serve more requests. Twenty that each get a real share of the CPU
finish in a second; a hundred fighting over one core all take twenty.

### Swap

DigitalOcean droplets ship with **no swap**. Without it `kswapd` burns CPU
trying to reclaim memory with nowhere to put anything.

```bash
fallocate -l 2G /swapfile && chmod 600 /swapfile
mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

Older `mkswap` has no `-q` flag. If `mkswap` fails after `fallocate` has
succeeded, you are left with a 2 GB file consuming disk and doing nothing.

## nginx in front of Apache

Optional, and worth it on any host with real traffic. nginx takes ports 80 and
443, answers page-cache hits and static files itself, and hands only genuine
renders to Apache, which moves to port 8443. A cached page then costs
milliseconds instead of a prefork process.

Two example files ship with the engine:

| File | Goes to |
|---|---|
| `etc/nginx/nginx.conf.example` | `/etc/nginx/nginx.conf` |
| `etc/nginx/sites-available/ggcms.example.conf` | `/etc/nginx/sites-available/ggcms.conf` |

Replace every `***YOUR_..._HERE***` placeholder -- nginx refuses to start
while any remain, which is the point -- repeat the three server blocks at the
bottom of the site file once per site, then:

```bash
ln -s /etc/nginx/sites-available/ggcms.conf /etc/nginx/sites-enabled/ggcms.conf
nginx -t && systemctl reload nginx
```

Read the comments before changing a number. `open_file_cache max` is a file
descriptor count and must stay below what `worker_rlimit_nofile` allows, and
the page-cache rules must stay identical to `PageCache::IsCacheable_*` and to
`var/www/html/.htaccess`. Both files explain why.

## Database credentials

GGCMS reads its credentials from PHP's `mysqli` defaults, so `php.ini` is a
credential file on a live host. That is why it is gitignored.

In the **Apache** SAPI's `php.ini` (`/etc/php/8.x/apache2/php.ini`):

```ini
mysqli.default_user = <user>
mysqli.default_pw   = <password>
mysqli.default_host = <host>
mysqli.default_port = <port>
```

The CLI SAPI has its own `php.ini` and needs the same values, or the tools in
`cli/` will not connect.

> **Locate the database in the same region as the droplet.** The reference host
> runs in `nyc1` against a database in `nyc3`, which puts a ~5 ms round trip on
> every query. At roughly 1,700 queries per page render that is nine seconds of
> pure latency. See [PageCache.md](PageCache.md).

```bash
CREATE DATABASE <name> CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
```

`utf8mb4` is required, not preferred. The engine stores content in many
scripts, and `utf8` (three-byte) will corrupt it.

`cli/sql/clonefrom.sql` is the base schema for a new site.

## Installing a domain

> Content and log directories are named forwards -- `/srv/ggcms/example.com/`
> -- because they are addressed by hostname. **The configuration directory is
> named in reverse-DNS order**, `/etc/ggcms/com.example/`, because every
> loader builds that path through `ReverseDomainName()`. A forward-named
> config directory is not merely untidy, it is unreachable. See
> [CodeConventions.md](CodeConventions.md).

```bash
mkdir --mode=755 /srv/ggcms/example.com/www/image
mkdir --mode=755 /var/log/ggcms/example.com/stats
mkdir --mode=755 /etc/ggcms/com.example
chown -R www-data /srv/ggcms /var/log/ggcms /etc/ggcms
```

Then the vhost, and the certificate:

```bash
a2ensite example.com.conf
a2dissite 000-default.conf
certbot --apache -d example.com
ufw allow 80 && ufw allow 443
a2enmod ssl && systemctl restart apache2
```

`install_domain.php` in `cli/scripts/internal/domain/` automates much of this,
and `check_domain.php` verifies the result across certificates, Apache config,
filesystem and database. Run the checker afterwards — it is thorough and it is
faster than finding out later:

```bash
php /usr/lib/ggcms/cli/scripts/internal/domain/check_domain.php example.com y
```

## DNS

```bash
doctl compute domain records create example.com --record-type "A" \
  --record-name "example.com" --record-ttl 3600 --record-data "<ipv4>"
doctl compute domain records create example.com --record-type "A" \
  --record-name "*.example.com" --record-ttl 3600 --record-data "<ipv4>"
```

`AAAA` records take the IPv6 address in the same shape. `CAA` records are
awkward through `doctl` and are usually easier in the control panel.

Verify with `check_domain_records.php` rather than by eye.

## Nice to have

Nothing in this section is required to serve a page. Each entry is here because
its absence has already cost an hour of somebody's morning.

### sysstat

```bash
apt-get install -y sysstat
sed -i 's/^ENABLED="false"/ENABLED="true"/' /etc/default/sysstat
systemctl restart sysstat
```

The package ships disabled on Ubuntu, so the install alone does nothing. With
collection on, `sar -r` answers "how much memory was in use overnight" the next
morning. Without it that question has no answer at all, because the only
evidence was in RAM and RAM is where the problem went.

This was installed on 3 September 2026, the morning after `MaxRequestWorkers`
was raised from twenty to forty on a prediction. The prediction was sound and
the ceiling held, but confirming it required a reading taken at the moment of
asking; the overnight peak, which is the number that actually matters, was
simply gone.

**Read memory with PSS, never with summed RSS.** Prefork workers share most of
their pages with the parent, so adding up `RSS` counts the same memory forty
times over. On this host the two disagree by a factor of seven -- 1,891 MB by
summed RSS against 261 MB of real usage:

```bash
tot=0; n=0
for p in $(pgrep apache2); do
  v=$(awk '/^Pss:/{print $2}' /proc/$p/smaps_rollup 2>/dev/null)
  [ -n "$v" ] && tot=$((tot+v)) && n=$((n+1))
done
echo "$n workers, $((tot/1024)) MB PSS total, $((tot/n/1024)) MB each"
```

`mods-available/mpm_prefork.conf` explains the same trap at greater length, and
its worker count was chosen from PSS. An RSS reading will tell you this host is
out of memory when it is using an eighth of what it has.

## Before you call it done

Read [Operations.md](Operations.md) and install the crontab in it **now**,
while you are still thinking about this host.

The production host ran for 848 days with no scheduled diagnostics. Its disk
filled, which stopped `certbot` from renewing, which expired every certificate,
which put a browser security warning in front of seventeen live sites for
thirteen months. The tools that would have caught each step already existed in
`cli/`. None of them was on a timer.

An installation is not finished when the site loads. It is finished when the
host can tell you it is unwell.
