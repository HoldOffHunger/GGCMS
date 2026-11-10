-- EarthFluent, 10 Nov 2026: a find-and-replace swapped "German" (and "French") for each course's own language, catching
-- Germany and French fries; a broken form saved its own "Entry Translation" label as titles; one Indonesian phrase was
-- filed as the English. Every update is guarded by the broken value it replaces.
SET NAMES utf8mb4;
START TRANSACTION;

-- English titles
UPDATE Entry SET Title='Germany', ListTitle='Germany', LastModificationDate=NOW() WHERE id IN (52914,54252,55530,56804,58019) AND Title IN ('Japanesey','Hindiy','Dutchy','Portuguesey','Koreany');
UPDATE Entry SET Title='Berlin, Germany', ListTitle='Berlin, Germany', LastModificationDate=NOW() WHERE id IN (53122,54455,55733,57016,58122) AND Title LIKE 'Berlin, %y';
UPDATE Entry SET Title='French fries', ListTitle='French fries', LastModificationDate=NOW() WHERE id IN (58688,58800,58905,59013,59122,59230,59340) AND Title LIKE '% fries';
UPDATE Entry SET Title='What do you do for work?', ListTitle='What do you do for work?', LastModificationDate=NOW() WHERE id IN (61475,70978,73219,75606) AND Title='What does do you work?';
UPDATE Entry SET Title='energy bar', ListTitle='energy bar', Subtitle='', LastModificationDate=NOW() WHERE id=58475 AND Title='Entry Translation';
UPDATE Entry SET Title='That looks terrible.', ListTitle='That looks terrible.', Subtitle='', LastModificationDate=NOW() WHERE id=65239 AND Title='That looks terrible.Entry Translation';
UPDATE Entry SET Title='I know.', ListTitle='I know.', LastModificationDate=NOW() WHERE id=74890 AND Title='Aku tahu.';
SELECT 'titles' AS step, COUNT(*) AS fixed FROM Entry WHERE id IN (52914,53122,54252,54455,55530,55733,56804,57016,58019,58122,58688,58800,58905,59013,59122,59230,59340,61475,70978,73219,75606,58475,65239,74890) AND Title IN ('Germany','Berlin, Germany','French fries','What do you do for work?','energy bar','That looks terrible.','I know.');

-- Translations
UPDATE EntryTranslation SET Title='ドイツ', LastModificationDate=NOW() WHERE id=42306 AND Title='日本語';
UPDATE EntryTranslation SET Title='ベルリン、ドイツ', LastModificationDate=NOW() WHERE id=42472 AND Title='ベルリン、日本語';
UPDATE EntryTranslation SET Title='जर्मनी', LastModificationDate=NOW() WHERE id=43375 AND Title='Hindiy';
UPDATE EntryTranslation SET Title='बर्लिन, जर्मनी', LastModificationDate=NOW() WHERE id=43537 AND Title='बर्लिन, हिंदी';
UPDATE EntryTranslation SET Title='Duitsland', LastModificationDate=NOW() WHERE id=44395 AND Title='Dutchy';
UPDATE EntryTranslation SET Title='Berlijn, Duitsland', LastModificationDate=NOW() WHERE id=44557 AND Title='Berlijn, Dutchy';
UPDATE EntryTranslation SET Title='Alemanha', LastModificationDate=NOW() WHERE id=45414 AND Title='México';
UPDATE EntryTranslation SET Title='Berlim, Alemanha', LastModificationDate=NOW() WHERE id=45583 AND Title='Berlim, Português';
UPDATE EntryTranslation SET Title='독일', LastModificationDate=NOW() WHERE id=46380 AND Title='한국';
UPDATE EntryTranslation SET Title='베를린, 독일', LastModificationDate=NOW() WHERE id=46461 AND Title='베를린, 한국';
UPDATE EntryTranslation SET Title='Pommes frites', LastModificationDate=NOW() WHERE id=46999 AND Title='Bratkartoffeln';
UPDATE EntryTranslation SET Title='薯条', LastModificationDate=NOW() WHERE id=47168 AND Title='中国薯条';
UPDATE EntryTranslation SET Title='फ्रेंच फ्राइज़', LastModificationDate=NOW() WHERE id=47254 AND Title='हिंदी फ्राइज़';
UPDATE EntryTranslation SET Title='Kentang goreng', LastModificationDate=NOW() WHERE id=47339 AND Title='Kentang goreng indonesia';
UPDATE EntryTranslation SET Title='Friet', LastModificationDate=NOW() WHERE id=47426 AND Title='Hollandse frietjes';
UPDATE EntryTranslation SET Title='Que faites-vous dans la vie?', LastModificationDate=NOW() WHERE id=49078 AND Title='Que travaillez-vous?';
UPDATE EntryTranslation SET Title='お仕事は何ですか？', LastModificationDate=NOW() WHERE id=56682 AND Title='あなたは何をしていますか？';
UPDATE EntryTranslation SET Title='barra energética', Language='es', LastModificationDate=NOW() WHERE id=46740 AND Entryid=58475 AND Title='spanish' AND Language='en';
UPDATE EntryTranslation SET Title='यह भयानक लग रहा है।', Language='hi', LastModificationDate=NOW() WHERE id=52102 AND Entryid=65239 AND Title='hindi' AND Language='en';
INSERT INTO EntryTranslation (Title, Language, Entryid, OriginalCreationDate, LastModificationDate)
  SELECT 'Saya tahu', 'id', 74890, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM EntryTranslation WHERE Entryid=74890);
SELECT 'translations' AS step, COUNT(*) AS fixed FROM EntryTranslation WHERE id IN (42306,42472,43375,43537,44395,44557,45414,45583,46380,46461,46999,47168,47254,47339,47426,49078,56682,46740,52102) AND LastModificationDate > NOW() - INTERVAL 5 MINUTE;
SELECT 'indonesian I know' AS step, COUNT(*) AS rows_now FROM EntryTranslation WHERE Entryid=74890;
COMMIT;
