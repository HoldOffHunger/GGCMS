-- RecordChange to utf8mb4, 11 Nov 2026.  Two files, run by hand on prod, once per database -- the
-- preflight first, read, and only then the conversion:
--
--     for db in anarchistcode copyleftlicense holdoffhunger pronouncethat revoltlink; do
--         echo "== $db"; mysql -t $db < 2026-11-11-recordchange-utf8mb4-preflight.sql
--     done
--
--     for db in anarchistcode copyleftlicense holdoffhunger pronouncethat revoltlink; do
--         echo "== $db"; mysql -t $db < 2026-11-11-recordchange-utf8mb4.sql
--     done
--
-- (EarthFluent's ships converted in tonight's table swap.)  The names above are the sites; check each database's
-- real name with SHOW DATABASES before running.
--
-- Why: RecordChange is latin1_swedish_ci on these six sites and utf8mb3 everywhere else.  Since 1e81fbb a backup
-- that cannot be written stops the save, so on a latin1 site every edit or import whose old value holds anything
-- latin1 cannot -- Japanese, Chinese, Cyrillic, an em dash -- is refused rather than saved unrecorded.  Shipping
-- EarthFluent's Japanese and Chinese corrections met it: 1,067 of 1,139 rows refused.  Convert these five before
-- 1e81fbb is deployed, or their editors will meet it too.
--
-- utf8mb4 rather than utf8mb3: it is what clonefrom.sql now says, as it already does for Form and the error
-- instance tables, and it holds whatever the rest of a site holds.  RecordField's index, varchar(512), is 2,048
-- bytes in utf8mb4, inside InnoDB's 3,072.
--
-- Is CONVERT safe?  The engine never sets a connection charset, so it writes in the server's default, utf8mb4,
-- and MySQL either stored a value as true latin1 or refused it.  Then CONVERT turns every latin1 value into the
-- same characters in utf8mb4.  The preflight shows any row that is not plain ASCII, as text and as hex: a
-- value like "Ã©" where "é" was meant would mean the bytes were UTF-8 stored as latin1, and CONVERT would keep
-- the damage.  If the preflight shows that, stop and say so; the fix is a different ALTER (through BINARY).

-- The conversion.
ALTER TABLE RecordChange CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- After: utf8mb4_0900_ai_ci, and the same row count.
SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'RecordChange';
SELECT COUNT(*) AS rows_total FROM RecordChange;
