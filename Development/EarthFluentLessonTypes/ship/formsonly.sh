#!/bin/bash
# formsonly.sh <course> <code>  -- on prod: a course's verb forms only (never its lessons), cache kept, verb pages re-rendered
C=$1; L=$2
D=/mnt/nyc01/ef-types-ship/forms; cd $D
bash words.sh $D/prod-words-$C.tsv $C $L > /dev/null
if ! cmp -s prod-words-$C.tsv words-$C.tsv; then echo "prod's $C verb entries are not Fumiko's: nothing applied"; echo FORMSDONE; exit 1; fi
bash import.sh $D php forms-$C | head -1 > formsonly-$C.log; cat formsonly-$C.log
if ! grep -q "skipped 0 error 0" formsonly-$C.log; then echo "PREVIEW NOT CLEAN, nothing applied"; echo FORMSDONE; exit 1; fi
bash import.sh $D php forms-$C "--apply --keep-cache" | head -1
mysql -N -B earthfluent -e "SELECT CONCAT('/', c.Code, '/', t.Code, '/', l.Code, '/', w.Code, '/') FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid JOIN Assignment ca ON ca.Childid=c.id AND ca.Parentid=1 WHERE w.id IN (SELECT DISTINCT Entryid FROM WordForm WHERE Source='wiktionary' AND Language='$L')" > formsonly-urls-$C.txt
ok=0; while read -r url; do nice -n 19 php /usr/lib/ggcms/cli/scripts/internal/page_cache/warm_page_cache.php --domain=earthfluent.com --url="$url" --format=html > /dev/null 2>&1 && ok=$((ok+1)); done < formsonly-urls-$C.txt
echo "$C: re-rendered $ok of $(wc -l < formsonly-urls-$C.txt) verb pages"
mysql -N -B earthfluent -e "SELECT COUNT(*), COUNT(DISTINCT Entryid) FROM WordForm WHERE Source='wiktionary' AND Language='$L'"
echo FORMSDONE
