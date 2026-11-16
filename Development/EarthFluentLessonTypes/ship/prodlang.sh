#!/bin/bash
# prodlang.sh <course code> <language code>
# On prod: a course's verb forms and generated lessons, with the page cache kept, then the changed pages re-rendered.
C=$1; L=$2
D=/mnt/nyc01/ef-types-ship/forms; cd $D
cache() { find /mnt/nyc01/ggcms_cache/pages/earthfluent.com -type f | wc -l; }
bash words.sh $D/prod-words-$C.tsv $C $L > /dev/null
if ! cmp -s prod-words-$C.tsv words-$C.tsv; then echo "prod's $C verb entries are not Fumiko's: nothing applied"; echo LANGDONE; exit 1; fi
before=$(cache); echo "cached files before: $before"
for f in forms-$C lessons-$C; do bash import.sh $D php $f | head -1; done > preview-$C.log
cat preview-$C.log
if grep -vq "skipped 0 error 0" preview-$C.log || grep -q "new 0 " preview-$C.log; then echo "PREVIEW NOT CLEAN, nothing applied"; echo LANGDONE; exit 1; fi
for f in forms-$C lessons-$C; do bash import.sh $D php $f "--apply --keep-cache" | head -1; done
after=$(cache); echo "cached files after: $after"
if [ "$after" -lt $((before - 100)) ]; then echo "THE CACHE SHRANK: stopping before the re-render"; echo LANGDONE; exit 1; fi
WORD="SELECT CONCAT('/', c.Code, '/', t.Code, '/', l.Code, '/', w.Code, '/') FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid JOIN Assignment ca ON ca.Childid=c.id AND ca.Parentid=1"
{ echo /$C/patterns/; echo /$C/; mysql -N -B earthfluent -e "$WORD WHERE w.id IN (SELECT DISTINCT Entryid FROM WordForm WHERE Source='wiktionary' AND Language='$L')"; } > rewarm-$C.txt
done=0; failed=0
while read -r url; do
  if nice -n 19 php /usr/lib/ggcms/cli/scripts/internal/page_cache/warm_page_cache.php --domain=earthfluent.com --url="$url" --format=html > /dev/null 2>&1; then done=$((done+1)); else failed=$((failed+1)); echo "failed: $url"; fi
done < rewarm-$C.txt
echo "re-rendered $done of $(wc -l < rewarm-$C.txt), failed $failed"
mysql -N -B earthfluent -e "SELECT COUNT(*), COUNT(DISTINCT Entryid) FROM WordForm WHERE Source='wiktionary' AND Language='$L'; SELECT COUNT(*) FROM WordForm WHERE Source='forms' AND Language='$L'"
echo "cached files at the end: $(cache)"; uptime
echo LANGDONE
