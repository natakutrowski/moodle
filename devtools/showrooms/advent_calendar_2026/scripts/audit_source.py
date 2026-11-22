#!/usr/bin/env python3
"""Static sanity checks for the Advent Bridge source (no Moodle/server access)."""
import json
import re
import sys
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
src=(ROOT/'src/routes/index.tsx').read_text()
commerce=(ROOT/'src/bridge/commerce.tsx').read_text()
ctx=(ROOT/'src/bridge/advent-context.tsx').read_text()
locales={lang:json.loads((ROOT/'content'/f'{lang}.json').read_text())['page'] for lang in ('fr','en','ru')}
keys={lang:set(values) for lang,values in locales.items()}
errors=[]
if not(keys['fr']==keys['en']==keys['ru']):
 for lang in keys:
  errors += [f'{lang}: keys missing vs FR: {keys["fr"]-keys[lang]}']
used=set(re.findall(r'''\bt\(["']([^"']+)["']\)''', src+commerce))
used.update(f'story_{i}' for i in range(1,25))
for lang in locales:
 missing=used-keys[lang]
 if missing:errors.append(f'{lang}: referenced keys missing: {sorted(missing)}')
 empties=[k for k,v in locales[lang].items() if not isinstance(v,str) or not v.strip()]
 if empties:errors.append(f'{lang}: empty keys {empties}')
if re.search(r'''(?<![\w\d])(?:15\s*€|https?://(?:buy|pay|example)\.)''',src): errors.append('Hardcoded price or payment URL in page')
if 'new DOMParser()' not in ctx or 'data-showroom-final-legal' not in ctx:errors.append('No verified native legal refresh')
if 'cart_action.php' not in commerce or 'sesskey' not in commerce:errors.append('No guarded Commerce checkout')
assets=ROOT/'src/assets'
missing=[i for i in range(7,25) if not (assets/f'story-cover-{i}.webp').exists()]
noaudio=not (assets/'histoire-1-preview.mp3').exists()
print(f'CATALOGUES: {len(keys["fr"])} FR/EN/RU keys each; {len(used)} distinct translation keys referenced')
print('STORIES: 24 canonical French titles in all locales')
print('MISSING MEDIA:',('covers '+','.join(map(str,missing))) if missing else 'no covers missing',
      '; audio preview absent' if noaudio else '; audio preview present')
if errors:
 print('FAIL:',*errors,sep='\n - ')
 sys.exit(1)
print('OK: static translation/Commerce integration audit (NOT an E2E test)')

# X3 editorial consistency: titles and hand-lettered French signatures stay FR in all locales.
import json as _json
from pathlib import Path as _Path
_catalog = _Path(__file__).resolve().parents[1] / 'content'
_ref = _json.loads((_catalog / 'fr.json').read_text(encoding='utf-8'))['page']
_forced = ['decor_hero', 'decor_original', 'decor_gift_card', 'decor_gift_end', 'decor_goodbye'] + ['story_' + str(i) for i in range(1, 25)]
for _lang in ('ru', 'en'):
    _page = _json.loads((_catalog / (_lang + '.json')).read_text(encoding='utf-8'))['page']
    for _key in _forced:
        if _page[_key] != _ref[_key]:
            raise SystemExit('X3 editorial French-only text mismatch: ' + _lang + '.' + _key)
print('X3: 24 story titles and 5 decorative French phrases preserved in all locales')

# V4: exact technical regressions that must not return in a later build.
_src=src
_visual=(ROOT/'scripts'/'bridge-visual-overrides.css').read_text()
_final=(ROOT/'scripts'/'finalize.mjs').read_text()
_require_classes=('advent-section-pill','advent-hero-badge','advent-cat-image-frame',
                  'advent-faq-frame','advent-faq-trigger','advent-faq-answer',
                  'advent-door-front','advent-door-back','advent-gift-paper')
for _class in _require_classes:
 if _class not in _src or _class not in _visual:
  raise SystemExit('V4 required visual selector missing: '+_class)
if 'Campus<sup' in _src:
 raise SystemExit('V4 duplicate Hero CampusFR logo has returned')
if 'cursor-pointer' not in _src or 'cursor: pointer' not in _visual:
 raise SystemExit('V4 FAQ cursor missing')
if 'CAMPUSFR_ADVENT_BRIDGE_VISUAL_V4' not in _final:
 raise SystemExit('V4 final compiled CSS injection missing')
print('V4: Shadow DOM visual overrides, Hero logo removal and FAQ affordance checked')
