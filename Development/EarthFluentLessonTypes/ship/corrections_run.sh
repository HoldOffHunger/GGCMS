#!/bin/bash
# corrections_run.sh <folder> <php> [prod] <language>...
# Corrections to verb forms and lessons already on the site, from forms-update-<language>.sql and
# lessons-update-<language>.sql in <folder> (make_forms_update.py, make_lessons_update.py), and the lit pieces a lesson
# row lost, from pieces-delete-<language>.sql, imported with --table=WordPiece. Each database finds its own rows; every
# file is previewed, and nothing is applied unless every preview is clean. With "prod" the page cache is kept and the
# pages that changed are re-rendered one at a time.
D=$1; PHP=$2; shift 2
PROD=""; [ "$1" = "prod" ] && { PROD=prod; shift; }
cd "$D" || exit 1
IMPORT=/usr/lib/ggcms/cli/scripts/internal/entries/import_entries.php
FORMS_HEAD='Entry_id\tWordForm_id\tWordForm_FormText\n'
LESSONS_HEAD='Entry_id\tEntry_Title\tEntryTranslation_id\tEntryTranslation_Title\tDescription_id\tDescription_Description\tWordPiece_id\tWordPiece_Piece\tWordForm_id\tWordForm_BuiltFromEntryid\tDelete\n'
PIECES_HEAD='Entry_id\tWordPiece_id\tDelete\n'
WORD="SELECT CONCAT('/', c.Code, '/', t.Code, '/', l.Code, '/', w.Code, '/') FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid JOIN Assignment ca ON ca.Childid=c.id AND ca.Parentid=1"
LESSON="SELECT DISTINCT CONCAT('/', c.Code, '/', t.Code, '/', l.Code, '/') FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid JOIN Assignment ca ON ca.Childid=c.id AND ca.Parentid=1"
run() { table=""; case $1 in pieces-delete-*) table="--table=WordPiece";; esac
        $PHP $IMPORT earthfluent.com --file=$D/$1.tsv --user=ADMIN --limit=0 $table $2 --report=$D/report-$1.txt > /dev/null 2>&1
        echo "$1 exit $?: rows $(( $(wc -l < $1.tsv) - 1 )) updated $(grep -c 'Updated old record$' report-$1.txt) deleted $(grep -c 'Deleted old record$' report-$1.txt) unchanged $(grep -c 'Unchanged$' report-$1.txt) skipped $(grep -c 'Skipped$' report-$1.txt) error $(grep -c 'Error$' report-$1.txt)"; }
files=(); : > corrections-urls.txt
for l in "$@"; do
  for name in forms-update lessons-update pieces-delete; do
    [ -s $name-$l.sql ] || continue
    case $name in forms-update) head=$FORMS_HEAD;; lessons-update) head=$LESSONS_HEAD;; pieces-delete) head=$PIECES_HEAD;; esac
    ( printf "$head"; mysql -N -B --default-character-set=utf8mb4 earthfluent < $name-$l.sql ) > $name-$l.tsv
    files+=("$name-$l")
    ids=$(awk -F'\t' 'NR>1 {print $1}' $name-$l.tsv | sort -un | paste -sd, -)
    [ -n "$ids" ] && mysql -N -B earthfluent -e "$WORD WHERE w.id IN ($ids); $LESSON WHERE w.id IN ($ids)" >> corrections-urls.txt
  done
done
for f in "${files[@]}"; do run $f ""; done > corrections-preview.log; cat corrections-preview.log
grep -h -A3 "Skipped$\|Error$" report-*-update-*.txt report-pieces-delete-*.txt 2>/dev/null | head -12
if grep -vq "skipped 0 error 0" corrections-preview.log; then echo "PREVIEW NOT CLEAN, nothing applied"; echo CORRECTIONSDONE; exit 1; fi
FLAGS="--apply"; [ -n "$PROD" ] && FLAGS="--apply --keep-cache"
for f in "${files[@]}"; do run $f "$FLAGS"; done
if [ -n "$PROD" ]; then
  sort -u corrections-urls.txt > corrections-urls-u.txt; ok=0
  while read -r url; do nice -n 19 $PHP /usr/lib/ggcms/cli/scripts/internal/page_cache/warm_page_cache.php --domain=earthfluent.com --url="$url" --format=html > /dev/null 2>&1 && ok=$((ok+1)); done < corrections-urls-u.txt
  echo "re-rendered $ok of $(wc -l < corrections-urls-u.txt)"
fi
echo CORRECTIONSDONE
