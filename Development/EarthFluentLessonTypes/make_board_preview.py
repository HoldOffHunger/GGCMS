"""Writes board-preview.html: every cell of every grid-<language>.json as a lesson board, on one page.

    python make_board_preview.py [path to EarthFluent's theme.css]

For looking at the board before the lessons are in a database. It prints the markup the lesson template will print
(see var/www/html/javascript/lesson-board.js), and uses the site's own theme and the site's own script, so what is
on this page is what a lesson will show. The page is a throwaway: it is not committed.
"""
import html
import json
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
THEME = Path(r"E:\GreenGluonCMS_Unhireable\usr\lib\ggcms\src\templates\earthfluent\theme.css")
SCRIPT = HERE.parent.parent / "var" / "www" / "html" / "javascript" / "lesson-board.js"
# The engine's tokens the theme leans on, so the page stands alone
TOKENS = (':root { --serif: "Literata", Georgia, "Times New Roman", serif; --sans: "Archivo", "Helvetica Neue", Arial, sans-serif; '
          '--gutter: clamp(16px, 4vw, 40px); } body { margin: 0; background: var(--ground); color: var(--ink); font-family: var(--sans); } '
          '.block { margin: 2.5rem auto; } h1, h2 { font-family: var(--serif); } .preview-top { padding: 1.5rem var(--gutter); } '
          '.preview-top a { margin-right: 1rem; }')
TYPES = {"patterns": "Patterns", "sentence-building": "Sentence Building"}


def marked(foreign: str, marks: list[str]) -> str:
    # Wrap each mark's first unclaimed appearance, left to right, so "¿" and "?" or two は's each get their own
    spans, start = [], 0
    for mark in marks:
        at = foreign.find(mark, start)
        if at < 0:
            at = foreign.find(mark)
        if at < 0 or any(a < at + len(mark) and at < b for a, b in spans):
            continue
        spans.append((at, at + len(mark)))
        start = at + len(mark)
    out, last = [], 0
    for a, b in sorted(spans):
        out += [html.escape(foreign[last:a]), "<mark>", html.escape(foreign[a:b]), "</mark>"]
        last = b
    return "".join(out) + html.escape(foreign[last:])


def board(cell: dict, job: dict, grid: dict, strategies: dict) -> str:
    code, language = grid["translation_language"], grid["language"].capitalize()
    rows = []
    for row in cell["rows"]:
        source = f' data-from="{row["from"]}"' if "from" in row else ""
        rows.append(f'<li class="board-row"{source}><span class="board-foreign" lang="{code}">{marked(row["foreign"], [mark["text"] if isinstance(mark, dict) else mark for mark in row.get("mark", [])])}</span>'
                    f'<span class="board-english">{html.escape(row["english"])}</span></li>')
    how = " and ".join(f"<b>{html.escape(name)}</b>" for name in cell["strategy"])
    return (f'<section class="block lesson-block lesson-board" id="{code}-{job["code"]}" data-strategy="{html.escape(cell["strategy"][0])}" '
            f'aria-label="On the board">'
            f'<h2>{TYPES[job["type"]]} - {html.escape(job["name"])}</h2>'
            f'<p class="lesson-hint board-asks">{html.escape(job["asks"])}</p>'
            f'<div class="board-slate"><p class="board-strategy">{language} does this with: {how}</p>'
            f'<ol class="board-rows">{"".join(rows)}</ol>'
            f'<p class="board-rule">{html.escape(cell["description"])}</p></div></section>')


def main() -> None:
    theme = Path(sys.argv[1]) if len(sys.argv) > 1 else THEME
    spine = json.loads((HERE / "spine.json").read_text(encoding="utf-8"))
    jobs = {job["code"]: job for job in spine["jobs"]}
    grids = [json.loads(path.read_text(encoding="utf-8")) for path in sorted(HERE.glob("grid-*.json"))]
    links = "".join(f'<a href="#{grid["translation_language"]}">{grid["language"].capitalize()}</a>' for grid in grids)
    body = [f'<div class="preview-top"><h1>The lesson board, every cell</h1><p>{links}</p></div>']
    for grid in grids:
        body.append(f'<div class="preview-top" id="{grid["translation_language"]}"><h1>{grid["language"].capitalize()}</h1></div>')
        body += [board(cell, jobs[cell["job"]], grid, spine["strategies"]) for cell in grid["cells"]]
    page = ('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            f'<title>Lesson board preview</title><style>{TOKENS}\n{theme.read_text(encoding="utf-8")}</style></head><body>'
            + "".join(body) + f'<script>{SCRIPT.read_text(encoding="utf-8")}</script></body></html>')
    (HERE / "board-preview.html").write_text(page, encoding="utf-8")
    print("written:", HERE / "board-preview.html", f"({sum(len(grid['cells']) for grid in grids)} boards)")


if __name__ == "__main__":
    main()
