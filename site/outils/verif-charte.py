#!/usr/bin/env python3
"""Verifie un bloc WordPress contre la checklist §9 de la charte Kayli Clinn v2."""
import re, sys, unicodedata

PALETTE = {'#0D2340','#1A3A5C','#1F2937','#4B5563','#6B7280','#E5E7EB','#F3F4F6',
           '#FFFFFF','#FFF','#F7F8FA','#0FA7A5','#0B8483','#076E6D','#F0FAFA','#E6F5F5','#19E3DF','#8A5A06'}  # #8A5A06 = badge « Exemple » (charte §4)

def parts(s):
    css = ''.join(re.findall(r'<style[^>]*>(.*?)</style>', s, re.S))
    body = re.sub(r'<script[^>]*>.*?</script>', '', re.sub(r'<style[^>]*>.*?</style>', '', s, flags=re.S), flags=re.S)
    body = re.sub(r'<!--.*?-->', '', body, flags=re.S)
    js = ''.join(re.findall(r'<script(?![^>]*ld\+json)[^>]*>(.*?)</script>', s, re.S))
    vis = re.sub(r'[ \t\r\n]+', ' ', re.sub(r'<[^>]+>', ' ', body))
    return css, body, js, vis

def verifier(chemin):
    s = open(chemin, encoding='utf-8').read()
    css, body, js, vis = parts(s)
    r = []
    ok = lambda c, m: r.append((c, m))

    ok('fonts.googleapis' not in s and 'fonts.gstatic' not in s, "aucun lien Google Fonts")
    ok(not re.search(r'(Fraunces|Montserrat|Roboto:|Poppins|Playfair)', css), "aucune police hors charte")
    sans_masque = re.sub(r'[^;{}]*mask-image[^;}]*', '', css)
    ok('gradient' not in sans_masque, "aucun dégradé (les masques de fondu ne comptent pas)")
    ok('backdrop-filter' not in css, "aucun effet verre")
    ok(not re.search(r'(?<!-)animation:\s*(?!none)[a-zA-Z]', css), "aucune animation automatique")
    ok(not re.search(r'color:\s*#fff[^;}]*;[^}]*background:\s*#0FA7A5', css, re.I), "texte blanc jamais sur #0FA7A5")

    hors = sorted({c.upper() for c in re.findall(r'#[0-9a-fA-F]{3,6}\b', re.sub(r'[^;{}]*mask-image[^;}]*', '', css))} - PALETTE)
    ok(not hors, "aucune couleur hors palette" + (" — trouvées : " + ", ".join(hors[:12]) if hors else ""))

    acts = re.findall(r'<(?:a|button|summary)\b[^>]*>', body)
    sans = [a for a in acts if 'data-kc-event' not in a and 'href="#' not in a]
    ok(not sans, "repères data-kc-event sur les %d éléments cliquables (%d sans)" % (len(acts), len(sans)))

    ok(':focus-visible' in css, ":focus-visible déclaré")
    ok('prefers-reduced-motion' in css, "prefers-reduced-motion respecté")
    ok('!' not in re.sub(r'&[a-z]+;', '', vis), "aucun point d'exclamation")
    ok('rendez-vous' not in vis.lower(), "aucun « rendez-vous »")
    emo = [c for c in vis if ord(c) > 0x2190 and unicodedata.category(c) == 'So']
    ok(not emo, "aucun emoji")
    ok(not re.search(r'\d (?:€|%|h\b|m²)', vis), "espaces insécables avant € % h m²")
    ok('&nbsp;' not in js, "aucune entité HTML dans le JavaScript")
    import html as _h
    vis2 = _h.unescape(vis).replace('\u00a0', ' ')
    dp = [m for m in re.findall(r'[a-zéèêàù] : ([A-Za-zÀ-ÿ][^.;]{0,60})', vis2)
          if ',' not in m[:40] and '—' not in m and '–' not in m]
    ok(not dp, "aucune phrase à deux-points sans liste" + (" — " + dp[0][:60] if dp else ""))
    secs = {re.search(r'class="([^"]+)"', t).group(1).split()[0]
            for t in re.findall(r'<section[^>]*class="[^"]+"[^>]*>', body) if re.search(r'class="([^"]+)"', t)}
    sombres = sorted(c for c in secs if re.search(r'\.' + re.escape(c) + r'\s*\{[^}]*background:\s*#0D2340', css))
    ok(True, "sections sur fond sombre : %s (la charte §3 en recommande une)" % (", ".join(sombres) or "aucune"))
    durs = re.findall(r'url\((["\']?)https?://[^"\')]+\1\)', css)
    ok(True, "images en url() directes : %d (doivent toutes venir du panneau photos)" % len(durs))
    return r

for f in sys.argv[1:]:
    print("──", f)
    for c, m in verifier(f):
        print(("  OK  " if c else "  KO  ") + m)
