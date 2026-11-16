#!/bin/bash
# run.sh <folder> <php> [prod]
# The corrections to lessons already on the site, for four languages: finds this database's own rows, previews, applies.
# With "prod" it keeps the page cache and re-renders the pages that changed (the lessons, their words, the Patterns page).
D=$1; PHP=$2; PROD=$3; cd "$D" || exit 1
IMPORT=/usr/lib/ggcms/cli/scripts/internal/entries/import_entries.php
HEAD='Entry_id\tEntry_Title\tEntryTranslation_id\tEntryTranslation_Title\tDescription_id\tDescription_Description\tDelete\n'
run() { $PHP $IMPORT earthfluent.com --file=$D/$1.tsv --user=ADMIN --limit=0 $2 --report=$D/report-$1.txt > /dev/null 2>&1
        echo "$1 exit $?: updated $(grep -c 'Updated old record$' report-$1.txt) deleted $(grep -c 'Deleted old record$' report-$1.txt) unchanged $(grep -c 'Unchanged$' report-$1.txt) skipped $(grep -c 'Skipped$' report-$1.txt) error $(grep -c 'Error$' report-$1.txt)"; }
: > urls.txt
WORD="SELECT CONCAT('/', c.Code, '/', t.Code, '/', l.Code, '/', w.Code, '/') FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid JOIN Assignment ca ON ca.Childid=c.id AND ca.Parentid=1"
LESSON="SELECT DISTINCT CONCAT('/', c.Code, '/', t.Code, '/', l.Code, '/') FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid JOIN Assignment ca ON ca.Childid=c.id AND ca.Parentid=1"
for l in spanish french italian portuguese; do
  ( printf "$HEAD"; mysql -N -B --default-character-set=utf8mb4 earthfluent < lessons-update-$l.sql ) > lessons-update-$l.tsv
  echo "$l rows found: $(( $(wc -l < lessons-update-$l.tsv) - 1 ))"
  # the pages to re-render are read before anything is deleted: the words whose English changes, and their lessons
  ids=$(awk -F'\t' 'NR>1 && $7!="Y" {print $1}' lessons-update-$l.tsv | sort -un | paste -sd, -)
  [ -n "$ids" ] && mysql -N -B earthfluent -e "$WORD WHERE w.id IN ($ids); $LESSON WHERE w.id IN ($ids)" >> urls.txt
  echo "/$l/patterns/" >> urls.txt
done
( printf 'Entry_id\tEntryTranslation_id\tCurrent_EntryTranslation_Title\tEntryTranslation_Title\n'
  mysql -N -B --default-character-set=utf8mb4 earthfluent -e "SELECT x.Entryid, x.id, x.Title, 'fonder' FROM EntryTranslation x JOIN Entry w ON w.id=x.Entryid JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid AND t.Code='verbs' WHERE w.Title='found' AND x.Language='fr' AND x.Title='a trouvé'" ) > found-french.tsv
fid=$(awk -F'\t' 'NR>1 {print $1}' found-french.tsv | paste -sd, -); [ -n "$fid" ] && mysql -N -B earthfluent -e "$WORD WHERE w.id IN ($fid); $LESSON WHERE w.id IN ($fid)" >> urls.txt
for f in lessons-update-spanish lessons-update-french lessons-update-italian lessons-update-portuguese found-french; do run $f ""; done > preview.log; cat preview.log
grep -h -A3 "Skipped$\|Error$" report-*.txt | head -16
if grep -vq "skipped 0 error 0" preview.log; then echo "PREVIEW NOT CLEAN, nothing applied"; echo FIXDONE; exit 1; fi
if [ "$PROD" = "prod" ]; then FLAGS="--apply --keep-cache"; else FLAGS="--apply"; fi
for f in lessons-update-spanish lessons-update-french lessons-update-italian lessons-update-portuguese found-french; do run $f "$FLAGS"; done
if [ "$PROD" = "prod" ]; then
  sort -u urls.txt > urls-u.txt; ok=0
  while read -r url; do nice -n 19 $PHP /usr/lib/ggcms/cli/scripts/internal/page_cache/warm_page_cache.php --domain=earthfluent.com --url="$url" --format=html > /dev/null 2>&1 && ok=$((ok+1)); done < urls-u.txt
  echo "re-rendered $ok of $(wc -l < urls-u.txt)"
fi
mysql -N -B --default-character-set=utf8mb4 earthfluent -e "SELECT 'command lessons left', c.Code, COUNT(*) FROM Entry l JOIN Assignment a ON a.Childid=l.id JOIN Entry t ON t.id=a.Parentid AND t.Code='patterns' JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid WHERE l.Title LIKE 'Patterns - Telling Someone to Do It, Part %' GROUP BY c.Code; SELECT 'orphan words', COUNT(*) FROM Entry w LEFT JOIN Assignment a ON a.Childid=w.id WHERE a.id IS NULL AND w.id>1; SELECT 'I found out', COUNT(*) FROM Entry WHERE Title IN ('I found out','She found out'); SELECT 'I ask for', COUNT(*) FROM Entry WHERE Title='I ask for'"
echo FIXDONE
