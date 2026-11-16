#!/bin/bash
# verblist2.sh <course code> <language code>  -- the course's Verbs words, lowercased, one a line
mysql -N -B --default-character-set=utf8mb4 earthfluent -e "SELECT DISTINCT LOWER(x.Title) FROM Entry w JOIN Assignment wa ON wa.Childid=w.id JOIN Entry l ON l.id=wa.Parentid JOIN Assignment la ON la.Childid=l.id JOIN Entry t ON t.id=la.Parentid AND t.Code='verbs' JOIN Assignment ta ON ta.Childid=t.id JOIN Entry c ON c.id=ta.Parentid AND c.Code='$1' JOIN EntryTranslation x ON x.Entryid=w.id AND x.Language='$2'" > /mnt/d/ef-types-ship/forms/verbs-$1.txt
wc -l < /mnt/d/ef-types-ship/forms/verbs-$1.txt
