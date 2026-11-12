-- Preflight for 2026-11-11-recordchange-utf8mb4.sql, which says why.  Reads only.

SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'RecordChange';
SELECT COUNT(*) AS rows_total, SUM(CONVERT(OldValue USING ascii) <> OldValue) AS rows_not_ascii FROM RecordChange;
SELECT id, RecordType, RecordField, LEFT(OldValue, 60) AS OldValue, HEX(LEFT(OldValue, 30)) AS OldValue_hex FROM RecordChange WHERE CONVERT(OldValue USING ascii) <> OldValue ORDER BY id DESC LIMIT 10;

-- Old values latin1 could not hold may not have been refused but stored as question marks, depending on the
-- session's sql_mode -- on Fumiko, 日本語 into latin1 became ???.  Those are lost already and CONVERT cannot bring
-- them back; this counts the rows that look like it, so the loss is known rather than found later.
SELECT COUNT(*) AS rows_with_runs_of_question_marks FROM RecordChange WHERE OldValue LIKE '%??%';
SELECT id, Entryid, RecordType, RecordField, Recordid, LEFT(OldValue, 60) AS OldValue FROM RecordChange WHERE OldValue LIKE '%??%' ORDER BY id DESC LIMIT 10;
