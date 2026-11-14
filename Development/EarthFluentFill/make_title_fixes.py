"""Mend the broken English in EarthFluent's master phrases, in every course, with the translations that copied it.

Some of Italian's English titles were typed wrong ("Where are we go?", "My coming is not coming.") and every course
carries the same title on its own entry. The older courses then translated the mistake word for word ("Mi venida no
viene", "Voy a la parte a las 7"), and two Hindi entries hold a tool's error text ("Translation error",
"Translating..."). This writes one import file for import_entries.php that

    - retitles every entry with a broken title, whatever course it is in
    - corrects each translation listed below, but only where it still reads what is listed (Current_ guards it), so
      a course that already says the right thing, like the fill lessons, is left alone
    - rewords each lesson description that quotes a broken title

Every row is guarded by the value it changes, so a second run changes nothing. Ids are read from the database the
file is for, since Fumiko's and prod's differ:

    python make_title_fixes.py --sql > title_fixes.sql
    mysql -N -B --default-character-set=utf8mb4 earthfluent < title_fixes.sql > title-fixes-found.tsv
    python make_title_fixes.py title-fixes-found.tsv title-fixes.tsv
    import_entries.php earthfluent.com --file=title-fixes.tsv --user=ADMIN --limit=0      (preview, then --apply)
"""
import csv
import sys

TITLES = {
    "Where are we go?": "Where are we going?",
    "My coming is not coming.": "I am not coming anymore.",
    "I have have no opinion.": "I have no opinion.",
    "I am going to the part at 7.": "I am going to the party at 7.",
    "IS it snowing?": "Is it snowing?",
    "Evey week.": "Every week.",
    "Everyday.": "Every day.",
    "I get up everyday at 8 AM.": "I get up every day at 8 AM.",
    "What time do you go to work everyday?": "What time do you go to work every day?",
    "I go to work everyday at 8:00 AM.": "I go to work every day at 8:00 AM.",
    "I get out of work at 4:00 PM everyday.": "I get out of work at 4:00 PM every day.",
    "I have to take shower.": "I have to take a shower.",
    "How long will you be in Aoteoroa for?": "How long will you be in Aotearoa for?",
    "My name is john.": "My name is John.",
    "Can I have some water.": "Can I have some water?",
    "What's the address of the hospital.": "What's the address of the hospital?",
    "Would you like to buy this.": "Would you like to buy this?",
    "Not recently": "Not recently.",
    "I hope you and the family have a nice trip": "I hope you and the family have a nice trip.",
    "Nevermind.": "Never mind.",
    # garbled in meaning, not just in spelling; Ben's rewording of 13 Nov 2026
    "I am going to the capital across 1,000 miles.": "The capital is 1,000 miles away.",
    "I went to school with work.": "I went to school while I worked.",
    "How much does it cost in hours?": "How much does it cost per hour?",
    "No, but we don't have that in a size 11.": "No, we don't have that in a size 11.",
    "Do you prefer either more?": "Do you prefer one more than the other?",
    "I can tell you where I am near.": "I can tell you what I am near.",
    "How far away until May 1st?": "How long until May 1st?",
}

CAPITAL, SCHOOL, HOURS = "I am going to the capital across 1,000 miles.", "I went to school with work.", "How much does it cost in hours?"
SIZE, EITHER, NEAR, MAY = "No, but we don't have that in a size 11.", "Do you prefer either more?", "I can tell you where I am near.", "How far away until May 1st?"

# (broken English title, translation language): (what the translation says now, what it should say)
TRANSLATIONS = {
    ("My coming is not coming.", "es"): ("Mi venida no viene.", "Ya no vengo."),
    ("My coming is not coming.", "it"): ("La mia venuta non verrà.", "Non vengo più."),
    ("My coming is not coming.", "ja"): ("私の来るのは来ない。", "もう行きません。"),
    ("My coming is not coming.", "zh"): ("我来不来了", "我不来了。"),
    ("My coming is not coming.", "hi"): ("मेरा आना नहीं आ रहा है", "मैं अब नहीं आऊँगा।"),
    ("My coming is not coming.", "id"): ("Kedatangan saya tidak datang", "Saya tidak jadi datang."),
    ("I am going to the part at 7.", "es"): ("Voy a la parte a las 7.", "Voy a la fiesta a las 7."),
    ("I am going to the part at 7.", "fr"): ("Je vais à la partie à 7.", "Je vais à la fête à 7 heures."),
    ("I am going to the part at 7.", "it"): ("Vado alla parte alle 7.", "Vado alla festa alle 7."),
    ("I am going to the part at 7.", "ja"): ("私は7時にその部分に行く。", "7時にパーティーに行きます。"),
    ("I am going to the part at 7.", "zh"): ("我将在7点去参加。", "我七点去参加派对。"),
    ("I am going to the part at 7.", "hi"): ("मैं 7 पर भाग में जा रहा हूँ।", "मैं 7 बजे पार्टी में जा रहा हूँ।"),
    ("I am going to the part at 7.", "id"): ("Saya akan ke bagian di 7.", "Saya akan ke pesta jam 7."),
    ("I have have no opinion.", "it"): ("Posso avere la tua opinione su qualcosa? Non ho opinioni.", "Non ho un'opinione."),
    ("I have have no opinion.", "hi"): ("Translating...", "मेरी कोई राय नहीं है।"),
    ("I have have no opinion.", "zh"): ("我没有意见", "我没有意见。"),
    ("Evey week.", "hi"): ("Translation error", "हर हफ़्ते।"),
    ("Where are we go?", "hi"): ("हम कहाँ जाते हैं?", "हम कहाँ जा रहे हैं?"),
    ("Where are we go?", "zh"): ("我们去哪里", "我们去哪里？"),
    ("IS it snowing?", "ja"): ("雪が降っています？", "雪が降っていますか？"),
    ("Can I have some water.", "es"): ("Puedo tomar un poco de agua.", "¿Puedo tomar un poco de agua?"),
    ("Can I have some water.", "fr"): ("Puis-je avoir de l'eau.", "Puis-je avoir de l'eau ?"),
    ("Can I have some water.", "it"): ("Posso avere dell'acqua.", "Posso avere dell'acqua?"),
    ("Can I have some water.", "zh"): ("我可以要些水吗。", "我可以要些水吗？"),
    ("Can I have some water.", "hi"): ("क्या मुझे पानी मिल सकता है।", "क्या मुझे पानी मिल सकता है?"),
    ("Can I have some water.", "id"): ("Dapatkah saya minum air.", "Bolehkah saya minta air?"),
    ("What's the address of the hospital.", "it"): ("Qual è l'indirizzo dell'ospedale.", "Qual è l'indirizzo dell'ospedale?"),
    ("What's the address of the hospital.", "zh"): ("医院的地址是什么", "医院的地址是什么？"),
    ("What's the address of the hospital.", "hi"): ("अस्पताल का पता क्या है", "अस्पताल का पता क्या है?"),
    ("What's the address of the hospital.", "id"): ("Apa alamat rumah sakit.", "Apa alamat rumah sakitnya?"),
    ("Would you like to buy this.", "it"): ("Vuoi comprare questo.", "Vuoi comprare questo?"),
    ("Would you like to buy this.", "zh"): ("你想买这个吗", "你想买这个吗？"),
    ("Not recently", "es"): ("No recientemente", "Recientemente no."),
    ("Not recently", "fr"): ("Pas récemment", "Pas récemment."),
    ("Not recently", "it"): ("Non di recente", "Non di recente."),
    ("Not recently", "ja"): ("最近ではない", "最近はありません。"),
    ("Not recently", "zh"): ("不是最近", "最近没有。"),
    ("Not recently", "hi"): ("हाल ही में नहीं है", "हाल ही में नहीं।"),
    ("I hope you and the family have a nice trip", "de"): ("Ich hoffe, Sie und die Familie haben eine schöne Reise", "Ich hoffe, Sie und die Familie haben eine schöne Reise."),
    ("I hope you and the family have a nice trip", "it"): ("Spero che tu e la famiglia abbia un bel viaggio", "Spero che tu e la famiglia facciate un bel viaggio."),
    ("I hope you and the family have a nice trip", "zh"): ("我希望你和家人有一个愉快的旅行", "我希望你和家人有一个愉快的旅行。"),
    ("I hope you and the family have a nice trip", "hi"): ("मुझे आशा है कि आप और परिवार की एक अच्छी यात्रा होगी", "मुझे आशा है कि आप और परिवार की यात्रा अच्छी होगी।"),
    ("I hope you and the family have a nice trip", "id"): ("Saya harap Anda dan keluarga melakukan perjalanan yang menyenangkan", "Saya harap Anda dan keluarga melakukan perjalanan yang menyenangkan."),
    ("I hope you and the family have a nice trip", "ja"): ("私はあなたと家族が楽しい旅をしたいと願っています", "ご家族と楽しい旅になりますように。"),

    (CAPITAL, "de"): ("Ich fahre über 1.000 Meilen in die Hauptstadt.", "Die Hauptstadt ist 1.000 Meilen entfernt."),
    (CAPITAL, "es"): ("Voy a la capital a través de 1.000 millas.", "La capital está a 1.000 millas."),
    (CAPITAL, "fr"): ("Je vais à la capitale, à 1 000 milles d'ici.", "La capitale est à 1 000 milles d'ici."),
    (CAPITAL, "hi"): ("मैं 1000 मील की दूरी पर राजधानी जा रहा हूं", "राजधानी 1,000 मील दूर है।"),
    (CAPITAL, "id"): ("Saya pergi ke ibukota melintasi 1.000 mil.", "Ibu kota berjarak 1.000 mil."),
    (CAPITAL, "it"): ("Andrò alla capitale attraverso 1.000 miglia.", "La capitale è a 1.000 miglia."),
    (CAPITAL, "ja"): ("私は1,000マイルに渡って首都に行く。", "首都は1,000マイル離れています。"),
    (CAPITAL, "nl"): ("Ik ga naar de hoofdstad over een afstand van 1.000 mijl.", "De hoofdstad is 1.000 mijl hiervandaan."),
    (CAPITAL, "zh"): ("我要去千里之外的首都。", "首都在一千英里以外。"),
    (CAPITAL, "ko"): ("1,000마일 떨어진 수도로 가고 있어요.", "수도는 1,000마일 떨어져 있어요."),
    (CAPITAL, "pl"): ("Jadę do stolicy oddalonej o 1000 mil.", "Stolica jest oddalona o 1000 mil."),
    (CAPITAL, "pt"): ("Vou para a capital a uma distância de 1.000 milhas.", "A capital fica a 1.000 milhas daqui."),
    (CAPITAL, "ru"): ("Я еду в столицу за тысячу миль.", "Столица находится в тысяче миль отсюда."),

    (SCHOOL, "de"): ("Ich bin mit der Arbeit zur Schule gegangen.", "Ich bin zur Schule gegangen, während ich gearbeitet habe."),
    (SCHOOL, "es"): ("Fui a la escuela con el trabajo.", "Fui a la escuela mientras trabajaba."),
    (SCHOOL, "fr"): ("Je suis allé à l'école avec le travail.", "J'allais à l'école pendant que je travaillais."),
    (SCHOOL, "hi"): ("मैं काम के साथ स्कूल गया", "मैं काम करते हुए स्कूल गया।"),
    (SCHOOL, "it"): ("Sono andato a scuola con lavoro.", "Andavo a scuola mentre lavoravo."),
    (SCHOOL, "ja"): ("私は仕事で学校に行きました。", "働きながら学校に通いました。"),
    (SCHOOL, "nl"): ("Ik ging naar school met werk.", "Ik ging naar school terwijl ik werkte."),
    (SCHOOL, "zh"): ("我上班去工作。", "我一边工作一边上学。"),
    (SCHOOL, "ko"): ("일하면서 학교에 갔어요.", "일하면서 학교에 다녔어요."),
    (SCHOOL, "pl"): ("Poszedłem do szkoły z pracą.", "Chodziłem do szkoły, pracując."),
    (SCHOOL, "pt"): ("Eu fui à escola com o trabalho.", "Eu estudava enquanto trabalhava."),
    (SCHOOL, "ru"): ("Я пошёл в школу по работе.", "Я учился в школе и одновременно работал."),

    (HOURS, "es"): ("¿Cuánto cuesta en horas?", "¿Cuánto cuesta por hora?"),
    (HOURS, "fr"): ("Combien ça coûte en heures?", "Combien ça coûte par heure ?"),
    (HOURS, "hi"): ("घंटे में कितना खर्च होता है?", "प्रति घंटा कितना खर्च होता है?"),
    (HOURS, "id"): ("Berapa biayanya dalam hitungan jam?", "Berapa biayanya per jam?"),
    (HOURS, "it"): ("Quanto costa in ore?", "Quanto costa all'ora?"),
    (HOURS, "ja"): ("時間内にどれくらいの費用がかかりますか？", "1時間あたりいくらですか？"),
    (HOURS, "nl"): ("Hoeveel kost het in uren?", "Hoeveel kost het per uur?"),
    (HOURS, "zh"): ("几小时内花费多少？", "每小时多少钱？"),
    (HOURS, "ko"): ("시간으로 계산하면 얼마예요?", "시간당 얼마예요?"),
    (HOURS, "pt"): ("Quanto custa em horas?", "Quanto custa por hora?"),
    (HOURS, "ru"): ("Сколько это стоит в часах?", "Сколько это стоит в час?"),

    (SIZE, "de"): ("Nein, aber wir haben das nicht in Größe 11.", "Nein, das haben wir nicht in Größe 11."),
    (SIZE, "es"): ("No, pero no tenemos eso en un tamaño 11.", "No, no lo tenemos en talla 11."),
    (SIZE, "fr"): ("Non, mais nous n'en avons pas dans une taille 11.", "Non, nous ne l'avons pas en taille 11."),
    (SIZE, "hi"): ("नहीं, लेकिन हमारे पास ऐसा आकार 11 में नहीं है", "नहीं, हमारे पास यह साइज़ 11 में नहीं है।"),
    (SIZE, "id"): ("Tidak, tapi kami tidak memilikinya dalam ukuran 11.", "Tidak, kami tidak punya ukuran 11."),
    (SIZE, "it"): ("No, ma non lo abbiamo in una dimensione 11.", "No, non lo abbiamo nella taglia 11."),
    (SIZE, "nl"): ("Nee, maar we hebben dat niet in maat 11.", "Nee, we hebben dat niet in maat 11."),
    (SIZE, "zh"): ("不，但我们没有这样的大小11。", "不，我们没有11号的。"),
    (SIZE, "ko"): ("아니요, 그런데 11 사이즈는 저희한테 없어요.", "아니요, 11 사이즈는 없어요."),
    (SIZE, "pl"): ("Nie, ale nie mamy tego w rozmiarze 11.", "Nie, nie mamy tego w rozmiarze 11."),
    (SIZE, "pt"): ("Não, mas não temos esse tamanho 11.", "Não, não temos esse no tamanho 11."),
    (SIZE, "ru"): ("Нет, но у нас нет такого размера одиннадцатого.", "Нет, у нас нет одиннадцатого размера."),

    (EITHER, "de"): ("Bevorzugst du mehr?", "Magst du eins lieber als das andere?"),
    (EITHER, "es"): ("¿Prefiere usted más?", "¿Prefieres uno más que el otro?"),
    (EITHER, "fr"): ("Vous préférez plus?", "Tu préfères l'un plus que l'autre ?"),
    (EITHER, "hi"): ("क्या आप या तो अधिक पसंद करते हैं?", "क्या आपको एक दूसरे से ज़्यादा पसंद है?"),
    (EITHER, "id"): ("Apakah kamu lebih suka lagi?", "Apakah kamu lebih suka salah satunya?"),
    (EITHER, "it"): ("Preferisci di più?", "Preferisci uno più dell'altro?"),
    (EITHER, "ja"): ("あなたはどちらかを好むか？", "どちらかの方が好きですか？"),
    (EITHER, "nl"): ("Wil je liever meer?", "Vind je de een leuker dan de ander?"),
    (EITHER, "zh"): ("你更喜欢吗？", "你更喜欢其中一个吗？"),
    (EITHER, "ko"): ("더 선호하세요?", "둘 중에 하나가 더 좋아요?"),
    (EITHER, "pl"): ("Wolisz więcej?", "Wolisz jedno bardziej niż drugie?"),
    (EITHER, "pt"): ("Você prefere mais?", "Você prefere um mais do que o outro?"),
    (EITHER, "ru"): ("Вы предпочитаете что-то больше?", "Тебе одно нравится больше другого?"),

    (NEAR, "de"): ("Ich kann dir sagen, wo ich in der Nähe bin.", "Ich kann dir sagen, was in meiner Nähe ist."),
    (NEAR, "es"): ("Puedo decirte dónde estoy cerca.", "Puedo decirte qué tengo cerca."),
    (NEAR, "fr"): ("Je peux vous dire où je suis proche.", "Je peux vous dire ce qu'il y a près de moi."),
    (NEAR, "hi"): ("मैं आपको बता सकता हूँ कि मैं कहां रहा हूँ।", "मैं आपको बता सकता हूँ कि मैं किसके पास हूँ।"),
    (NEAR, "id"): ("Saya dapat memberitahu Anda di mana saya dekat.", "Saya bisa memberi tahu Anda saya ada di dekat apa."),
    (NEAR, "it"): ("Posso dirti dove sono vicino.", "Posso dirti cosa ho vicino."),
    (NEAR, "ja"): ("私はどこにいるのかを教えてくれる。", "近くに何があるか教えられます。"),
    (NEAR, "nl"): ("Ik kan je vertellen waar ik in de buurt ben.", "Ik kan je vertellen waar ik vlakbij ben."),
    (NEAR, "zh"): ("我可以告诉你我在哪里。", "我可以告诉你我在什么附近。"),
    (NEAR, "ko"): ("제가 근처 어디에 있는지 말씀드릴 수 있어요.", "제가 무엇 근처에 있는지 말씀드릴 수 있어요."),
    (NEAR, "pl"): ("Mogę ci powiedzieć, gdzie jestem w pobliżu.", "Mogę ci powiedzieć, w pobliżu czego jestem."),
    (NEAR, "pt"): ("Eu posso te dizer onde estou perto.", "Posso te dizer perto do que eu estou."),
    (NEAR, "ru"): ("Я могу сказать тебе, где я нахожусь поблизости.", "Я могу сказать тебе, рядом с чем я нахожусь."),

    (MAY, "es"): ("¿Qué tan lejos está hasta el 1 de mayo?", "¿Cuánto falta para el 1 de mayo?"),
    (MAY, "fr"): ("Jusqu'au 1er mai?", "Combien de temps jusqu'au 1er mai ?"),
    (MAY, "hi"): ("1 मई तक कितनी दूर?", "1 मई में कितना समय बाकी है?"),
    (MAY, "id"): ("Seberapa jauh sampai tanggal 1 Mei?", "Berapa lama lagi sampai tanggal 1 Mei?"),
    (MAY, "it"): ("Quanto lontano fino al 1 maggio?", "Quanto manca al 1 maggio?"),
    (MAY, "ja"): ("5月1日までどのくらい離れていますか？", "5月1日まであとどのくらいですか？"),
    (MAY, "zh"): ("到五月一号有多远？", "离五月一号还有多久？"),
}

HEADER = ["Entry_id", "Current_Entry_Title", "Entry_Title",
          "EntryTranslation_id", "Current_EntryTranslation_Title", "EntryTranslation_Title",
          "Description_id", "Current_Description_Description", "Description_Description"]


def sql() -> str:
    # what main() needs from the database: every entry with a broken title, with each of its translations, and every
    # lesson description that quotes one
    quote = lambda text: "'" + text.replace("'", "''") + "'"
    titles = ", ".join(quote(title) for title in TITLES)
    quoted = " or ".join("d.Description like " + quote(f'%"{title}"%') for title in TITLES)
    return (f"select 'word', w.id, w.Title, coalesce(t.id, ''), coalesce(t.Language, ''), coalesce(t.Title, '')\n"
            f"from Entry w left join EntryTranslation t on t.Entryid = w.id\nwhere binary w.Title in ({titles});\n"
            f"select 'description', d.Entryid, d.Description, d.id, d.Language, ''\nfrom Description d\nwhere {quoted};\n")


def main() -> None:
    if sys.argv[1:] == ["--sql"]:
        sys.stdout.reconfigure(encoding="utf-8")
        print(sql(), end="")
        return
    found, out = sys.argv[1], sys.argv[2]
    rows, counts = [HEADER], {"titles": 0, "translations": 0, "descriptions": 0}
    words = {}  # entry id -> row, so an entry's title and translation go in one row
    for line in open(found, encoding="utf-8"):
        kind, entry, text, other_id, language, other_text = (line.rstrip("\n").split("\t") + [""] * 6)[:6]
        if kind == "word" and text in TITLES:
            row = words.setdefault(entry, {"Entry_id": entry, "Current_Entry_Title": text, "Entry_Title": TITLES[text]})
            fix = TRANSLATIONS.get((text, language))
            if fix and other_text == fix[0]:
                row.update(EntryTranslation_id=other_id, Current_EntryTranslation_Title=fix[0], EntryTranslation_Title=fix[1])
                counts["translations"] += 1
        elif kind == "description":
            new = text
            for old, right in TITLES.items():
                new = new.replace(f'"{old}"', f'"{right}"')
            if new != text:
                rows.append([entry, "", "", "", "", "", other_id, text, new])
                counts["descriptions"] += 1
    counts["titles"] = len(words)
    rows[1:1] = [[row.get(name, "") for name in HEADER] for row in words.values()]
    with open(out, "w", encoding="utf-8", newline="") as file:
        csv.writer(file, delimiter="\t", lineterminator="\n", quoting=csv.QUOTE_MINIMAL).writerows(rows)
    print(counts)


if __name__ == "__main__":
    main()
