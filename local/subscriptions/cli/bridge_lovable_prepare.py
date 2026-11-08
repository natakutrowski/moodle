#!/usr/bin/env python3
"""X2.2: safe, non-executable preparation of a Lovable source ZIP into a Bridge workspace.

Does not npm install/build/deploy. All output stays outside Moodle's document root.
"""
from __future__ import annotations

from pathlib import Path, PurePosixPath
import json
import os
import re
import shutil
import stat
import tempfile
import zipfile

ALLOWED_ID = re.compile(r'^[a-z][a-z0-9_]{2,47}$')
MAX_FILES = 3000
MAX_TOTAL = 300 * 1024 * 1024
MAX_FILE = 32 * 1024 * 1024
SKIP_DIRS = {'.git', 'node_modules', '.next', '.output', 'dist', '.turbo', '.vercel', '.cache'}
SKIP_NAMES = {'.env', '.env.local', '.env.production', '.env.development', '.npmrc', '.yarnrc', '.pnp.cjs'}

class PrepareError(ValueError):
    pass

def check(condition: bool, reason: str) -> None:
    if not condition:
        raise PrepareError(reason)

def safe_path(name: str) -> bool:
    return bool(name) and '\\' not in name and not name.startswith('/') and all(
        part not in {'', '.', '..'} for part in name.split('/')
    )

def source_root(names: list[str]) -> str:
    """Reject ambiguous projects rather than silently taking a wrong app."""
    roots = set()
    for name in names:
        if name.endswith('package.json') and not any(x in name.split('/') for x in SKIP_DIRS):
            root = str(PurePosixPath(name).parent)
            roots.add('' if root == '.' else root + '/')
    check(len(roots) == 1, 'ZIP Lovable: exactement un package.json racine attendu')
    return next(iter(roots))

def detect_page(files: set[str]) -> tuple[str, str]:
    priority = [('src/routes/index.tsx', 'tanstack'), ('src/pages/Index.tsx', 'spa'),
                ('src/pages/Home.tsx', 'spa'), ('src/App.tsx', 'spa'),
                ('src/routes/index.jsx', 'tanstack'), ('src/pages/Index.jsx', 'spa'),
                ('src/App.jsx', 'spa')]
    for filename, kind in priority:
        if filename in files:
            return filename, kind
    raise PrepareError('Page d’accueil non reconnue : src/routes/index.tsx, src/pages/Index.tsx ou src/App.tsx attendu')

def _copy_templates(srcroot: Path, designid: str, page: str, kind: str, label: str) -> None:
    assets = Path(__file__).parent / 'bridge_lovable_templates'
    check(assets.is_dir(), 'Modèles X2.2 manquants dans cli/bridge_lovable_templates')
    (srcroot / 'src/bridge').mkdir(parents=True, exist_ok=True)
    (srcroot / 'scripts').mkdir(exist_ok=True)
    (srcroot / 'content').mkdir(exist_ok=True)

    for template, destination in [
        ('campusfr-entry.tsx', 'src/bridge/campusfr-entry.tsx'),
        ('campusfr-i18n.tsx', 'src/bridge/campusfr-i18n.tsx'),
        ('vite.bridge.config.ts', 'vite.bridge.config.ts'),
        ('campusfr-texts.cjs', 'scripts/campusfr-texts.cjs'),
        ('campusfr-finalize.mjs', 'scripts/campusfr-finalize.mjs'),
        ('campusfr-gate.mjs', 'scripts/campusfr-gate.mjs'),
    ]:
        raw = (assets / template).read_text(encoding='utf-8')
        raw = (raw.replace('__CAMPUSFR_DESIGN_ID__', designid)
                   .replace('__CAMPUSFR_PAGE_IMPORT__', '../' + page[len('src/'):].rsplit('.', 1)[0]))
        if destination == 'src/bridge/campusfr-entry.tsx' and not (srcroot / 'src/styles.css').exists():
            raw = raw.replace('../styles.css', '../index.css')
        (srcroot / destination).write_text(raw, encoding='utf-8')

    manifest = {'id': designid, 'label': label, 'version': '0.1.0',
                'entry': 'dist/main.js', 'css': 'dist/style.css', 'content': 'content/ru.json'}
    (srcroot / 'manifest.campusfr.json').write_text(
        json.dumps(manifest, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')
    (srcroot / 'campusfr-prep.json').write_text(
        json.dumps({'designId': designid, 'page': page, 'kind': kind, 'translationsReviewed': False,
                    'commerceIntegrated': False, 'legalIntegrated': False, 'mediaIntegrated': False}, indent=2) + '\n',
        encoding='utf-8')
    packagepath = srcroot / 'package.json'
    package = json.loads(packagepath.read_text(encoding='utf-8'))
    check(isinstance(package, dict) and isinstance(package.get('dependencies'), dict),
          'package.json Lovable invalide (dependencies manquant)')
    package.setdefault('devDependencies', {})
    package.setdefault('scripts', {})
    package['scripts']['campusfr:extract'] = 'node scripts/campusfr-texts.cjs'
    package['scripts']['campusfr:build'] = ('node scripts/campusfr-gate.mjs && '
                                           'vite build --config vite.bridge.config.ts && '
                                           'node scripts/campusfr-finalize.mjs')
    package['scripts']['campusfr:build-preview'] = ('CAMPUSFR_BRIDGE_PREVIEW=1 vite build --config vite.bridge.config.ts && '
                                                   'CAMPUSFR_BRIDGE_PREVIEW=1 node scripts/campusfr-finalize.mjs')
    # This is a distinct Vite configuration: must not use Lovable's SSR entry/plugin.
    # Remove SSR-only Vite 8 dependencies / old rolldown pin; this is a standalone browser build.
    for dependency in ('@lovable.dev/vite-tanstack-config', '@tanstack/react-start',
                       '@tanstack/router-plugin', 'nitro'):
        package['devDependencies'].pop(dependency, None)
    if isinstance(package.get('overrides'), dict):
        package['overrides'].pop('rolldown', None)
        if not package['overrides']:
            del package['overrides']
    package['devDependencies']['vite'] = '7.3.7'
    package['devDependencies']['@vitejs/plugin-react'] = '5.2.0'
    package['devDependencies']['@tailwindcss/vite'] = '4.2.1'
    package['devDependencies']['typescript'] = '5.9.3'
    package['devDependencies']['tailwindcss'] = '4.2.1'
    package['dependencies'].setdefault('react', '19.2.0')
    package['dependencies'].setdefault('react-dom', '19.2.0')
    packagepath.write_text(json.dumps(package, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')
    notes = f'''# CampusFR X2.2 — Travail de préparation — {designid}

**PROJET NON APPROUVÉ / NE PAS DÉPLOYER SUR MOODLE.**
La page originale et tous les fichiers graphiques sont copiés sans modification dans ce workspace.

Page détectée : `{page}` ({kind}).

## Sur un poste de build isolé (PAS sur le serveur Moodle)

1. Examiner `package.json`, le code et les dépendances de ce ZIP non fiable.
2. Installer les dépendances de manière contrôlée ; ne jamais exécuter de scripts npm non audités.
3. `npm run campusfr:extract` — génère les textes éditables et la page Bridge (AST TypeScript).
4. Compléter et revoir FR / EN ; le russe est recopié provisoirement dans les trois langues.
5. **Adapter et vérifier Commerce** (prix, formulaire panier, dons, devises, propriété, preview),
   ainsi que mentions légales et vidéo Moodle. Aucun montant ou lien de paiement ne doit être statique.
6. `npm run campusfr:build-preview` — produit une maquette **NON validée** dans `../draft/{designid}`.
7. Revoir visuellement les sections, animations, responsive, styles Shadow DOM et les textes non extraits.
8. Après revue humaine, remplir `campusfr-approval.json` :
   `{{"commerceIntegrated":true,"legalIntegrated":true,"mediaIntegrated":true,
      "translationsReviewed":true,"approvedBy":"Prénom Nom","reviewedAt":"YYYY-MM-DD"}}`.
9. `npm run campusfr:build` et `python3 bridge_lovable.py pack ../ready/{designid} ...`
   uniquement après validation du JS/CSS, du contrat et des parcours commerciaux.

Le fichier `campusfr-approval.json` est un **verrou organisationnel local**, pas une signature cryptographique.
L’installation DEV nécessite ensuite une approbation SHA-256 explicite via X2.1.

## Limites de l'automatisation

- Seuls les textes JSX statiques sûrs sont convertis en composants `<BridgeText>` éditables.
  Les chaînes dans les props JS, les tableaux, les calculs, les images et les textes dynamiques
  restent à examiner dans `campusfr-report.json`.
- Les URL, boutons commerciaux et modalités de paiement **ne sont jamais auto-convertis**.
- Les traductions FR/EN générées sont provisoires ; elles ne sont PAS des traductions automatiques.
- Un projet Lovable non standard, avec SSR, portails DOM ou imports particuliers, peut exiger
  des ajustements. L’import ne garantit pas une reproduction visuelle à l’identique.
- Ne jamais utiliser `pack`/`install-dev` pour un build preview tant que les adaptations ne sont pas validées.
'''
    (srcroot.parent / 'README_CAMPUSFR_X22.md').write_text(notes, encoding='utf-8')


def prepare_lovable(zip_path: Path, designid: str, label: str, output: Path) -> dict:
    check(ALLOWED_ID.fullmatch(designid) is not None, 'ID invalide (a-z, 0-9 et _, longueur 3..48)')
    check(0 < len(label.encode('utf-8')) <= 150 and not any(ord(c) < 32 or c in '<>' for c in label),
          'Libellé invalide')
    check(zip_path.is_file(), 'Archive Lovable introuvable')
    check(not output.exists(), 'Le dossier de sortie existe déjà, aucun écrasement autorisé')
    check(not output.resolve().is_relative_to(Path('/var/www')), 'Écriture sous /var/www interdite')
    with zipfile.ZipFile(zip_path) as zf:
        entries = [e for e in zf.infolist() if not e.is_dir()]
        check(len(entries) <= MAX_FILES, 'Archive : trop de fichiers')
        names = [e.filename for e in entries]
        check(all(safe_path(n) for n in names) and len(set(names)) == len(names),
              'Chemin ZIP dangereux ou doublon')
        check(sum(e.file_size for e in entries) <= MAX_TOTAL, 'Archive trop volumineuse')
        for entry in entries:
            check(entry.file_size <= MAX_FILE, 'Fichier trop volumineux')
            check(not stat.S_ISLNK(entry.external_attr >> 16), 'Lien symbolique ZIP interdit')
        root = source_root(names)
        source_names = set()
        accepted = []
        skipped = []
        for entry in entries:
            if not entry.filename.startswith(root):
                # Files outside the project root are not silently included.
                check(root != '', 'Erreur de racine ZIP')
                skipped.append(entry.filename)
                continue
            rel = entry.filename[len(root):]
            if not rel:
                continue
            parts = PurePosixPath(rel).parts
            if (any(part in SKIP_DIRS or part.startswith('.env') for part in parts)
                    or parts[-1] in SKIP_NAMES
                    or '.env.' in parts[-1]):
                skipped.append(rel)
                continue
            if rel in {'package-lock.json', 'pnpm-lock.yaml', 'yarn.lock', 'bun.lock', 'bun.lockb'}:
                # Source package.json is changed for an isolated Vite build; old lockfiles are misleading.
                skipped.append(rel)
                continue
            accepted.append((entry, rel))
            source_names.add(rel)
        check('package.json' in source_names, 'package.json absent de la racine du projet')
        page, kind = detect_page(source_names)
        check('src/styles.css' in source_names or 'src/index.css' in source_names,
              'Feuille de styles principale non reconnue : src/styles.css ou src/index.css')
        check(not any(name.startswith('src/bridge/campusfr-') or name.startswith('scripts/campusfr-')
                      for name in source_names), 'Projet déjà adapté / fichiers CampusFR présents')
        output.parent.mkdir(parents=True, exist_ok=True)
        stage = Path(tempfile.mkdtemp(prefix='.campusfr_x22_', dir=output.parent))
        try:
            srcroot = stage / 'source'
            srcroot.mkdir()
            for entry, rel in accepted:
                path = srcroot / rel
                path.parent.mkdir(parents=True, exist_ok=True)
                with zf.open(entry) as source, path.open('wb') as target:
                    shutil.copyfileobj(source, target)
                path.chmod(0o644)
            _copy_templates(srcroot, designid, page, kind, label)
            report = {'designId': designid, 'sourceZip': zip_path.name, 'page': page,
                      'kind': kind, 'sourceFiles': len(accepted), 'omittedFiles': skipped,
                      'status': 'PREPARED_SOURCE_ONLY', 'approved': False,
                      'next': 'Run npm run campusfr:extract after review and dependency install outside Moodle'}
            (stage / 'campusfr-inspection.json').write_text(
                json.dumps(report, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
            for folder in sorted(stage.rglob('*')):
                if folder.is_dir():
                    folder.chmod(0o755)
            stage.chmod(0o755)
            stage.rename(output)
            return report
        finally:
            if stage.exists():
                shutil.rmtree(stage)
