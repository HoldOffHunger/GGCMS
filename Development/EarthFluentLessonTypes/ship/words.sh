#!/bin/bash
# words.sh <output file> [course code] [language code]  -- the course's Verbs words: entry id, tab, translation
C=${2:-spanish}; L=${3:-es}
mysql -N -B --default-character-set=utf8mb4 earthfluent -e "select w.id, x.Title from Entry w join EntryTranslation x on x.Entryid = w.id and x.Language = '$L' join Assignment wa on wa.Childid = w.id join Assignment la on la.Childid = wa.Parentid join Entry t on t.id = la.Parentid and t.Code = 'verbs' join Assignment ta on ta.Childid = t.id join Entry c on c.id = ta.Parentid and c.Code = '$C' order by w.id" > "$1"
wc -l < "$1"
