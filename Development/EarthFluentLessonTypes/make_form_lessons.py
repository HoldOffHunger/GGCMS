"""Makes lessons out of verb forms: for each verb in forms-recipes-<language>.json and each job that has a recipe there,
one lesson in the grid's own shape, written to lessons-<language>.json.

    python make_form_lessons.py spanish [how many verbs, default all]

Part 1 of every job is the grid's hand-written lesson, so the first verb is Part 2. A recipe says which four forms a
job shows and what the learner reads beside each; the forms themselves come from forms-<language>.json, which
make_forms.py read out of the dictionary. So nothing here is written by a model, or by hand, except the recipe.

What is lit on the board: a form that ends in one of its regular endings has that ending marked, whatever its stem
did. A form that ends in none of them is left unmarked, and the lesson says the verb goes its own way.

The output is checked by check_grid.py and imported by make_grid_import.py, like a grid file.
"""
import json
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent


def form_of(forms: dict, base: str, features: dict) -> str:
    return next(form["form"] for form in forms[base] if form["features"] == features)


def ending(recipes: dict, name: str, base: str, form: str) -> str:
    """The longest of the verb class's regular endings that the form ends in, or nothing"""
    last = base[-2:].replace("í", "i")
    found = [text for text in recipes["endings"][name].get(last, []) if form.endswith(text)]
    return max(found, key=len) if found else ""


def lesson(recipes: dict, forms: dict, job: str, part: int, verb: list) -> dict:
    recipe = recipes["recipes"][job]
    base, plain, she, past, ing = verb[:5]
    english = {"base": plain, "Base": plain[0].upper() + plain[1:], "s": she, "past": past, "ing": ing}
    stem, rows, unmarked, moved = base[:-2], [], False, False
    two = len(recipe["strategy"]) > 1
    for slot in recipe["rows"]:
        form = form_of(forms, base, slot["features"])
        text, marks = form, []
        if "helper" in slot:
            helper = form_of(forms, slot["helper"]["base"], slot["helper"]["features"])
            text = helper + " " + form
            marks.append({"text": helper, "role": "helper word"})
        if "wrap" in slot:
            text = slot["wrap"].format(form[0].upper() + form[1:] if slot["wrap"].startswith("¡{}") else form)
        if "word" in slot:
            marks.append({"text": slot["word"], "role": "helper word"})
        if "endings" in slot:
            piece = ending(recipes, slot["endings"], base, form)
            if piece:
                marks.append({"text": piece, "role": "ending"} if two or marks else piece)
                moved = moved or form[:-len(piece)] != stem  # salgo, busqué, tendré: the ending is regular and the rest is not
            else:
                unmarked = True
        row = {"english": slot["english"].format(**english), "foreign": text}
        if marks:
            row["mark"] = marks
        if "from" in slot:
            row["from"] = slot["from"]
        row["base"] = base
        row["features"] = slot.get("says", slot["features"])
        rows.append(row)
    # {1} to {4} in a recipe's description are the four rows as the learner sees them
    description = recipe["description"].format("", *[row["foreign"] for row in rows], Base=base[0].upper() + base[1:])
    if unmarked and "own_way" in recipe:
        description += recipe["own_way"].format(Base=base[0].upper() + base[1:])
    elif moved and "middle" in recipes:
        description += recipes["middle"]
    return {"job": job, "part": part, "verb": base, "strategy": recipe["strategy"], "description": description, "rows": rows}


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    language = sys.argv[1]
    recipes = json.loads((HERE / f"forms-recipes-{language}.json").read_text(encoding="utf-8"))
    forms = json.loads((HERE / f"forms-{language}.json").read_text(encoding="utf-8"))["verbs"]
    verbs = recipes["verbs"]["list"][:int(sys.argv[2])] if len(sys.argv) > 2 else recipes["verbs"]["list"]
    spine = [job["code"] for job in json.loads((HERE / "spine.json").read_text(encoding="utf-8"))["jobs"]]
    cells, parts = [], {}
    for job in [code for code in spine if code in recipes["recipes"]]:
        for verb in verbs:
            if verb[0] not in forms:
                print(f"no forms for {verb[0]}: run make_forms.py")
                return 1
            if recipes["recipes"][job].get("skip") in verb[5:]:
                continue
            parts[job] = parts.get(job, 1) + 1
            cells.append(lesson(recipes, forms, job, parts[job], verb))
    out = {"language": language, "translation_language": recipes["translation_language"], "generated": True,
           "status": "Made by make_form_lessons.py from forms-%s.json and the recipes in forms-recipes-%s.json: do not edit by hand. "
                     "Not yet checked by the translation workers." % (language, language),
           "cells": cells}
    head = json.dumps({key: value for key, value in out.items() if key != "cells"}, ensure_ascii=False, indent="\t")[:-2]
    blocks = []
    for cell in cells:
        top = json.dumps({key: value for key, value in cell.items() if key != "rows"}, ensure_ascii=False)[:-1]
        rows = ",\n".join("\t\t\t\t" + json.dumps(row, ensure_ascii=False) for row in cell["rows"])
        blocks.append("\t\t" + top + ', "rows": [\n' + rows + "\n\t\t\t]}")
    text = head + ',\n\t"cells": [\n' + ",\n".join(blocks) + "\n\t]\n}\n"
    (HERE / f"lessons-{language}.json").write_text(text, encoding="utf-8", newline="\n")
    own = sum(1 for cell in cells if "own way" in cell["description"])
    print(f"{len(cells)} lessons from {len(verbs)} verbs: " + ", ".join(f"{job} {count - 1}" for job, count in parts.items()) + f"; {own} say the verb goes its own way")
    return 0


if __name__ == "__main__":
    sys.exit(main())
