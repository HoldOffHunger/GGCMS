-- EarthFluent, 10 Nov 2026: the lesson blurbs the find-and-replace also reached, and two orphan copies of the work phrase.
SET NAMES utf8mb4;
START TRANSACTION;
UPDATE Entry SET Title='What do you do for work?', ListTitle='What do you do for work?', LastModificationDate=NOW() WHERE id IN (75456,75579) AND Title='What does do you work?';
UPDATE Description SET Description=REPLACE(Description, 'What does do you work?', 'What do you do for work?') WHERE Description LIKE '%What does do you work?%';
UPDATE Description SET Description=REGEXP_REPLACE(Description, '\\b(Italian|German|Japanese|Chinese|Hindi|Indonesian|Dutch) Fries\\b', 'French Fries') WHERE Description REGEXP '(Italian|German|Japanese|Chinese|Hindi|Indonesian|Dutch) Fries';
UPDATE Description SET Description=REGEXP_REPLACE(Description, '\\b(Japanese|Hindi|Dutch|Portuguese|Korean)y\\b', 'Germany') WHERE Description REGEXP '(Japanese|Hindi|Dutch|Portuguese|Korean)y';
SELECT 'blurbs still broken' AS step, COUNT(*) AS n FROM Description WHERE Description REGEXP 'Japanesey|Hindiy|Dutchy|Portuguesey|Koreany|(Italian|German|Japanese|Chinese|Hindi|Indonesian|Dutch) Fries|What does do you work';
SELECT id, Description FROM Description WHERE id IN (10646, 11798, 15192);
COMMIT;
