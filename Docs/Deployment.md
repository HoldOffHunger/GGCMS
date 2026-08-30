# Deployment

Historically there was no deployment process. The production host had no git
checkout of any kind; `cli/scripts/internal/source/backup_code.php` built a
tarball, and code reached the server by being copied there. This document
replaces that with a pull from Codeberg.

## The model

The repository tree mirrors a deployed host's filesystem, so deployment is a
copy to `/`. That property is worth preserving — it makes "what is on the
server" answerable by reading the repo.

The host keeps a checkout at `/opt/ggcms`, well away from the paths it serves
from. `bin/deploy.sh` pulls that checkout and syncs it into place.

```
Codeberg  --git pull-->  /opt/ggcms  --rsync-->  /usr/lib/ggcms
                                                 /etc/ggcms
                                                 /var/www/html
```

Nothing is served from `/opt/ggcms`, so a half-finished pull cannot be
executed by a visitor, and `.git` never sits inside a document root.

## First-time setup

If the Codeberg repository is public, the host needs no credentials at all —
an anonymous HTTPS clone can pull forever:

```bash
git clone https://codeberg.org/holdoffhunger/GreenGluonCMS.git /opt/ggcms
chmod +x /opt/ggcms/bin/deploy.sh
```

If it is private, generate a key on the host and register it on Codeberg as a
**deploy key with write access disabled**, so a compromised web server cannot
push:

```bash
ssh-keygen -t ed25519 -C "lamp-s-1vcpu-1gb-nyc1-01 deploy" -f /root/.ssh/id_ed25519_codeberg
cat /root/.ssh/id_ed25519_codeberg.pub    # paste into Codeberg → Settings → Deploy Keys
git clone git@codeberg.org:holdoffhunger/GreenGluonCMS.git /opt/ggcms
```

The checkout is roughly 200 MB with history. Confirm the disk can take it
before cloning — see [Operations.md](Operations.md).

## Deploying

```bash
/opt/ggcms/bin/deploy.sh
```

It will:

1. **Refuse to run on a full disk.** Under 512 MB free, it stops. A
   half-written sync is worse than no sync, and this host has been there.
2. `git pull --ff-only`. A divergent local state fails loudly instead of
   producing a merge commit nobody asked for.
3. rsync the engine to `/usr/lib/ggcms/` **with `--delete`**, so a file removed
   from the repository disappears from the host.
4. rsync `/etc/ggcms/` and `/var/www/html/` **without `--delete`**, because
   sites may hold untracked local configuration and the page cache lives under
   the document root.
5. `chmod +x` the CLI entry points. Git will not restore an execute bit that
   was never committed, and without one **even root cannot run them** — root
   bypasses read and write permission checks but not execute. This is the cause
   of `Permission denied` followed by `sudo: command not found`.
6. `chown -R www-data` and reset `/var/www` to 755.
7. Flush the page cache, because changed code makes every cached page suspect.
8. `apache2ctl configtest`, then reload Apache.

## What deployment does not touch

| | Why |
|---|---|
| `php.ini` | Holds the database credentials in `mysqli.default_*`. Not in the repository. |
| `/srv/ggcms/<domain>/` | Site content and uploaded images, gigabytes of it. Backed up separately. |
| `/var/log/` | Logs. |
| `/etc/apache2/sites-*` | Vhosts, managed by certbot. Changing these from a deploy would be a good way to take every site down at once. |
| Let's Encrypt anything | Managed by certbot and its timer. |
| The database | No migrations run automatically. |

## Rolling back

```bash
cd /opt/ggcms
git log --oneline -10
git checkout <sha>
./bin/deploy.sh
```

Then `git checkout main` once the cause is understood, so the host is not left
detached and quietly diverging from what everyone believes is deployed.

## Verifying

```bash
php /usr/lib/ggcms/cli/scripts/internal/domain/check_domain.php
curl -sS -o /dev/null -w '%{http_code} %{time_total}s\n' https://revoltlib.com/
```

Run the check from a machine that is *not* the server as well. A host that is
wholly unresponsive cannot tell you it is unresponsive.

## Why not push-to-deploy

A Codeberg webhook or a bare repo with a `post-receive` hook would deploy on
every push, which sounds convenient and removes the moment where a human
decides that now is a good time to change production. On a single 1 GB droplet
running seventeen live sites with no staging environment, that moment is worth
keeping. Pull deliberately.
