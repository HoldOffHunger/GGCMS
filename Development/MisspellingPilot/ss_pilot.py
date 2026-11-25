"""SS pilot: from Webster 1913 headwords, make dropped (ss->s) and tripled (ss->sss)
variants, and reject any variant that is itself a real word."""
import gzip, re, sys, json, glob, collections

DUMP = sys.argv[1]
LANG = sys.argv[2]   # .../classes/Language
OUT = sys.argv[3]

# --- parse Definition rows from the dump --------------------------------------
def tuples(line):
    i, n = line.index('VALUES ') + 7, len(line)
    while i < n:
        if line[i] != '(':
            i += 1
            continue
        i += 1
        row, cur, inq = [], [], False
        while i < n:
            c = line[i]
            if inq:
                if c == '\\':
                    cur.append(line[i + 1]); i += 2; continue
                if c == "'":
                    inq = False
                else:
                    cur.append(c)
            else:
                if c == "'":
                    inq = True
                elif c == ',':
                    row.append(''.join(cur)); cur = []
                elif c == ')':
                    row.append(''.join(cur)); i += 1; break
                else:
                    cur.append(c)
            i += 1
        yield row

terms = collections.Counter()
pos = {}
with gzip.open(DUMP, 'rt', encoding='utf-8', errors='replace') as f:
    for line in f:
        if not line.startswith('INSERT INTO `Definition`'):
            continue
        for row in tuples(line):
            for t in (row[1], row[2]):
                for w in re.split(r'[;,]\s*', t):
                    w = w.strip().lower()
                    if w:
                        terms[w] += 1
            w = row[1].strip().lower()
            if w and w not in pos and row[4].strip():
                pos[w] = row[4].strip()

real = set(terms)

# --- existing misspelling lists and US/UK spellings ---------------------------
def php_words(pattern):
    keys, vals = set(), set()
    for p in glob.glob(pattern):
        txt = open(p, encoding='utf-8').read()
        body = txt[txt.find('return ['):]
        for m in re.finditer(r"'((?:[^'\\]|\\.)*)'\s*=>", body):
            keys.add(m.group(1).replace("\\'", "'").lower())
        for m in re.finditer(r"'((?:[^'\\]|\\.)*)'\s*,", body):
            vals.add(m.group(1).replace("\\'", "'").lower())
        for m in re.finditer(r"=>\s*'((?:[^'\\]|\\.)*)'", body):
            vals.add(m.group(1).replace("\\'", "'").lower())
    return keys, vals

mis_keys, mis_vals = php_words(LANG + '/Words/EnglishMisspellings/*.php')
imis_keys, imis_vals = php_words(LANG + '/Words/IntensiveEnglishMisspellings/*.php')
ab_keys, ab_vals = php_words(LANG + '/Words/AmericanBritish/*.php')
real |= mis_keys | imis_keys | ab_keys | ab_vals
already = (mis_vals | imis_vals) - real

# Webster lists headwords, not inflections, so "idles" is not in the set even
# though "idle" is.  A variant that is a real word plus an inflectional ending
# is probably real.
def inflected(v):
    for end, back in (('ies', 'y'), ('es', ''), ('s', ''), ('ied', 'y'), ('ed', ''), ('ed', 'e'),
                      ('ing', ''), ('ing', 'e'), ('er', ''), ('er', 'e'), ('est', ''), ('ly', '')):
        if v.endswith(end) and len(v) > len(end) + 2:
            stem = v[:-len(end)] + back
            if end == 'es' and not re.search('(s|x|z|ch|sh|o)$', stem):
                continue
            if stem in real:
                return stem
            if end in ('ed', 'ing', 'er', 'est') and len(stem) > 3 and stem[-1] == stem[-2] and stem[:-1] in real:
                return stem[:-1]
    return ''

# --- generate ------------------------------------------------------------------
src = sorted(w for w in terms if 'ss' in w and re.fullmatch(r'[a-z]+', w) and 'sss' not in w)
rows, stats = [], collections.Counter()
for w in src:
    spots = [m.start() for m in re.finditer('ss', w)]
    for kind, rep in (('dropped', 's'), ('tripled', 'sss')):
        for at in spots:
            v = w[:at] + rep + w[at + 2:]
            if v in real:
                verdict = 'reject: real word'
            elif v in already:
                verdict = 'skip: already listed'
            elif kind == 'dropped' and inflected(v):
                verdict = 'suspect: inflection of ' + inflected(v)
            else:
                verdict = 'keep'
            stats[(kind, verdict.split(' of ')[0])] += 1
            rows.append((w, pos.get(w, ''), kind, v, verdict))

with open(OUT + '/ss_pilot.tsv', 'w', encoding='utf-8') as f:
    f.write('word\tpos\tkind\tvariant\tverdict\n')
    for r in rows:
        f.write('\t'.join(r) + '\n')

print('real-word set', len(real), '| ss source words', len(src), '| rows', len(rows))
for k, v in sorted(stats.items()):
    print(k, v)
