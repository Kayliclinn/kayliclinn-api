#!/usr/bin/env python3
"""Genere la version minifiee d'un bloc WordPress a partir de sa version .src.html.
Charte Kayli Clinn v2 §11 : commentaires internes retires, espaces retires,
noms de variables JavaScript raccourcis. Le JSON-LD n'est pas touche."""
import re, sys

RENAMES = [("noteForfait","F1"),("noteDevis","F2"),("cardCta","C"),("reduced","Q"),
           ("entries","E"),("amount","A"),("sizes","Z"),("sibs","G"),("root","R"),
           ("tabs","T"),("show","S"),("note","N"),("pan","P"),("list","L")]

def min_css(css):
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    css = re.sub(r'[ \t\r\n]+', ' ', css)
    css = re.sub(r'[ \t\r\n]*([{};:,>])[ \t\r\n]*', r'\1', css)
    css = re.sub(r';}', '}', css)
    return css.strip()

def min_js(js):
    js = re.sub(r'/\*.*?\*/', '', js, flags=re.S)
    out = []
    for line in js.split('\n'):
        if line.strip().startswith('//'):
            continue
        line = line.strip()
        if line:
            out.append(line)
    js = '\n'.join(out)
    # Les chaines sont mises de cote : un renommage ne doit jamais toucher leur contenu.
    lits = []
    def stash(m):
        lits.append(m.group(0))
        return '\x00%d\x00' % (len(lits) - 1)
    js = re.sub(r"'(?:\\.|[^'\\])*'|\"(?:\\.|[^\"\\])*\"", stash, js)
    for old, new in RENAMES:
        js = re.sub(r'\b%s\b' % old, new, js)
    js = re.sub(r'\x00(\d+)\x00', lambda m: lits[int(m.group(1))], js)
    return js

def min_html(h):
    h = re.sub(r'<!--(?!\[if).*?-->', '', h, flags=re.S)
    h = re.sub(r'>[ \t\r\n]+<', '><', h)
    h = re.sub(r'[ \t]*\n[ \t]*', '', h)
    return h.strip()

src = open(sys.argv[1], encoding='utf-8').read()
parts, pos, keep = [], 0, {}
for m in re.finditer(r'<(style|script)(\s[^>]*)?>(.*?)</\1>', src, re.S):
    tag, attrs, inner = m.group(1), m.group(2) or '', m.group(3)
    parts.append(('html', src[pos:m.start()])); pos = m.end()
    if tag == 'style':
        parts.append(('raw', '<style>%s</style>' % min_css(inner)))
    elif 'ld+json' in attrs:
        parts.append(('raw', '<script%s>%s</script>' % (attrs, re.sub(r'[ \t\r]*\n[ \t\r]*', '', inner).strip())))
    else:
        parts.append(('raw', '<script%s>%s</script>' % (attrs, min_js(inner))))
parts.append(('html', src[pos:]))
out = ''.join(min_html(c) if k == 'html' else c for k, c in parts)
open(sys.argv[2], 'w', encoding='utf-8').write(out + '\n')
print("%s -> %s : %d -> %d octets (-%d%%)" % (sys.argv[1], sys.argv[2], len(src), len(out), 100 - 100*len(out)//len(src)))
