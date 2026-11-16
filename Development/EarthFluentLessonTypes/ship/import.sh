#!/bin/bash
# import.sh <folder> <php> <file stem> [--apply]
DIR=$1; PHP=$2; F=$3; APPLY=$4
cd "$DIR" || exit 1
start=$(date +%s)
$PHP /usr/lib/ggcms/cli/scripts/internal/entries/import_entries.php earthfluent.com --file=$DIR/$F.tsv --user=ADMIN --limit=0 $APPLY --report=$DIR/report-$F.txt > /dev/null 2>&1
echo "$F exit $? in $(( $(date +%s) - start ))s: new $(grep -c 'New record' report-$F.txt) updated $(grep -c 'Updated old record' report-$F.txt) unchanged $(grep -c 'Unchanged' report-$F.txt) skipped $(grep -c 'Skipped' report-$F.txt) error $(grep -c 'Error' report-$F.txt)"
sed -n 1,14p report-$F.txt | cut -c1-200
grep -h -A3 "Skipped\|Error" report-$F.txt | head -12
echo IMPORTDONE
