-- Every course word as it is filed now: language, type, lesson, word, and the word's translation in its course.
-- Lessons with no words come out once with an empty word. Run against the database the import will go into:
--   mysql -N -B --default-character-set=utf8mb4 earthfluent < tree.sql > tree.tsv
select l.Code, t.Code, s.id, s.Title, s.Code, coalesce(d.Description, ''),
       coalesce(w.id, ''), coalesce(w.Title, ''), coalesce(w.Code, ''), coalesce(group_concat(distinct concat(tr.Language, '=', tr.Title) separator ' | '), '')
from Entry l
join Assignment al on al.Childid = l.id and al.Parentid = 1
join Assignment at on at.Parentid = l.id
join Entry t on t.id = at.Childid
join Assignment ast on ast.Parentid = t.id
join Entry s on s.id = ast.Childid
left join Description d on d.Entryid = s.id and d.Language = 'en'
left join Assignment aw on aw.Parentid = s.id
left join Entry w on w.id = aw.Childid
left join EntryTranslation tr on tr.Entryid = w.id
where l.Code in ('spanish','french','italian','german','japanese','chinese','hindi','indonesian','dutch','polish','portuguese','russian','korean')
group by l.Code, t.Code, s.id, s.Title, s.Code, d.Description, w.id, w.Title, w.Code;
