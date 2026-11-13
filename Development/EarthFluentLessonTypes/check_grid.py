"""Checks every grid-<language>.json beside this file against spine.json, by rule. No model, no database.

    python check_grid.py            every grid file
    python check_grid.py japanese   one language

What it holds a grid to: one cell for each of the spine's jobs, in the spine's order; a strategy from the spine's
six; four rows; every mark an exact piece of its row's foreign text; every "from" an earlier row of the same cell;
and every lesson a cell says it replaces really in that language's pilot file, under the same lesson type.
"""
import json
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
TYPES = {"patterns": "Patterns", "sentence-building": "Sentence Building"}


def check(path: Path, spine: dict) -> list[str]:
    grid, found = json.loads(path.read_text(encoding="utf-8")), []
    jobs = {job["code"]: job for job in spine["jobs"]}
    if [cell["job"] for cell in grid["cells"]] != list(jobs):
        found.append("the cells are not the spine's eighteen jobs, once each, in the spine's order")
    pilot = HERE / f"pilot-{grid['language']}.json"
    titles = {lesson["title"]: lesson["type"] for lesson in json.loads(pilot.read_text(encoding="utf-8"))["lessons"]} if pilot.exists() else {}
    for cell in grid["cells"]:
        job = cell["job"]
        if not cell.get("strategy") or set(cell["strategy"]) - set(spine["strategies"]):
            found.append(f"{job}: the strategy must be from the spine's six, found {cell.get('strategy')}")
        if not cell.get("description", "").strip():
            found.append(f"{job}: no description")
        if len(cell["rows"]) != 4:
            found.append(f"{job}: expected four rows, found {len(cell['rows'])}")
        replaces = cell.get("replaces")
        if replaces and titles.get(replaces) != jobs.get(job, {}).get("type"):
            found.append(f"{job}: replaces \"{replaces}\", which is not a {TYPES.get(jobs.get(job, {}).get('type'), '?')} lesson in {pilot.name}")
        for number, row in enumerate(cell["rows"], 1):
            if not row.get("english", "").strip() or not row.get("foreign", "").strip():
                found.append(f"{job}, row {number}: an empty side")
                continue
            found += [f"{job}, row {number}: the mark \"{mark}\" is not in \"{row['foreign']}\"" for mark in row.get("mark", [])
                      if mark not in row["foreign"]]
            if "from" in row and not 1 <= row["from"] < number:
                found.append(f"{job}, row {number}: \"from\" must be an earlier row of this cell, found {row['from']}")
            if ("features" in row) != ("base" in row) and "features" in row:
                found.append(f"{job}, row {number}: features without a base word")
    return found


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    spine = json.loads((HERE / "spine.json").read_text(encoding="utf-8"))
    paths = [HERE / f"grid-{name}.json" for name in sys.argv[1:]] or sorted(HERE.glob("grid-*.json"))
    failed = 0
    for path in paths:
        found = check(path, spine)
        failed += len(found)
        print(f"{path.name}: {'clean' if not found else str(len(found)) + ' to fix'}")
        for line in found:
            print("    " + line)
    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())
