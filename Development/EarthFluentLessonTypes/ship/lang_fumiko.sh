#!/bin/bash
# lang_fumiko.sh <course> <code> <verb to show> [--apply]
C=$1; L=$2; V=$3; APPLY=$4; D=/mnt/d/ef-types-ship/forms
cd /mnt/e/GGCMS && GGCMS_REPO=/mnt/e/GGCMS GGCMS_CONFIG_REPO=/mnt/e/GreenGluonCMS_Unhireable bash bin/local_sync.sh > /dev/null 2>&1
service apache2 status >/dev/null 2>&1 || service apache2 start >/dev/null 2>&1
for f in forms-$C lessons-$C; do bash $D/import.sh $D /opt/php85/bin/php $f $APPLY | head -1; done
[ -z "$APPLY" ] && exit 0
H='-H Host:earthfluent.com -H Cookie:loggedin=0'; B=https://localhost:8443
show() { curl -sk $H "$B$1" | grep -o '<ol class="board-rows">.*</ol>' | sed 's/<span class="board-english">\([^<]*\)<\/span>/  = \1/g; s/<li class="board-row"\( data-from="\([0-9]\)"\)\{0,1\}[^>]*>/\n   [\2] /g; s/<mark[^>]*>/[/g; s/<\/mark>/]/g; s/<[^>]*>//g'; echo; }
curl -sk $H "$B/$C/patterns/" -o /tmp/t.html -w "%{http_code} /$C/patterns/: "; echo "$(grep -o 'lesson-card-title' /tmp/t.html | wc -l) cards, $(grep -o 'lesson-card-more' /tmp/t.html | wc -l) strips, $(grep -c 'Fatal error\|Warning</b>' /tmp/t.html) faults"
for u in patterns-who-is-doing-it-part-4 patterns-now-before-and-later-part-4 patterns-still-going-or-finished-part-7 patterns-telling-someone-to-do-it-part-3; do echo "$u"; show /$C/patterns/$u/; done
path=$(mysql -N -B earthfluent -e "SELECT CONCAT('/$C/verbs/', l.Code, '/', w.Code, '/') FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid WHERE w.id=(SELECT Entryid FROM WordForm WHERE BaseWord='$V' AND Language='$L' AND FormText<>'' LIMIT 1)")
echo "$path"; curl -sk $H "$B$path" | grep -o '<section class="block lesson-block" id="forms".*</dl></section>' | sed 's/<tr>/\n/g; s/<div><dt>/\n/g; s/<[^>]*>/ /g' | sed 's/  */ /g'
