#!/bin/bash
# preview.sh <folder> <php> [flags]  -- every decisions-<code>.tsv through the importer; a preview unless flags say otherwise
D=$1; PHP=$2; FLAGS=$3
cd "$D" || exit 1
service mysql status > /dev/null 2>&1 || service mysql start > /dev/null 2>&1
mysql -N -B earthfluent -e "SELECT 'entries', COUNT(*), MAX(id) FROM Entry; SELECT 'translations', COUNT(*), MAX(id), MAX(LastModificationDate) FROM EntryTranslation"
for f in decisions-*.tsv; do
  c=${f#decisions-}; c=${c%.tsv}
  $PHP /usr/lib/ggcms/cli/scripts/internal/entries/import_entries.php earthfluent.com --file=$D/$f --user=ADMIN --limit=0 $FLAGS --report=$D/report-$c.txt > /dev/null 2>&1
  echo "$c exit $?: rows $(( $(wc -l < $f) - 1 )) updated $(grep -c 'Updated old record$' report-$c.txt) unchanged $(grep -c 'Unchanged$' report-$c.txt) skipped $(grep -c 'Skipped$' report-$c.txt) error $(grep -c 'Error$' report-$c.txt)"
done
echo PREVIEWDONE
