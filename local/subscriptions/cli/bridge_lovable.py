#!/usr/bin/env python3
"""CampusFR 7.98X.2.1 — inspect Lovable exports; package and deploy reviewed Bridge builds.

No arbitrary npm execution. No executable uploaded through the CMS. DEV only.
The compiled React entry must already implement the CampusFR Bridge v1 contract.
"""
from __future__ import annotations

import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import stat
import sys
import tempfile
import zipfile

ID_PATTERN = re.compile(r"^[a-z][a-z0-9_]{2,47}$")
VERSION_PATTERN = re.compile(r"^[0-9]+\.[0-9]+\.[0-9]+$")
KEY_PATTERN = re.compile(r"^[a-zA-Z][a-zA-Z0-9_]*$")
ALLOWED = {".js", ".css", ".png", ".jpg", ".jpeg", ".webp", ".gif", ".avif", ".woff", ".woff2"}
MAX_FILES = 600
MAX_TOTAL = 150 * 1024 * 1024
MAX_FILE = 32 * 1024 * 1024
MAX_MANIFEST = 8 * 1024
MAX_CATALOG = 512 * 1024
DEV_PLUGIN = Path('/var/www/moodle-dev/local/subscriptions')

class InvalidPackage(ValueError):
    pass

def ensure(cond: bool, message: str) -> None:
    if not cond:
        raise InvalidPackage(message)

def pathsafe(name: str) -> bool:
    parts = name.split('/')
    return (name != '' and not name.startswith('/') and '\\' not in name
            and all(p not in ('', '.', '..') for p in parts))

def inspect_zip(zip_path: Path) -> None:
    with zipfile.ZipFile(zip_path) as archive:
        entries = [x for x in archive.infolist() if not x.is_dir()]
        ensure(len(entries) <= 3000, 'Trop de fichiers dans le ZIP Lovable')
        names = [x.filename for x in entries]
        ensure(all(pathsafe(n) for n in names), 'Chemin suspect dans le ZIP Lovable')
        ensure(len(set(names)) == len(names), 'Fichiers ZIP en double')
        ensure(sum(x.file_size for x in entries) <= 300 * 1024 * 1024, 'Export Lovable trop volumineux')
        for x in entries:
            mode = x.external_attr >> 16
            ensure(not stat.S_ISLNK(mode), 'Lien symbolique interdit dans un ZIP')
        def candidates(suffix: str) -> list[str]:
            return [name for name in names if name == suffix or name.endswith('/' + suffix)]
        main = candidates('package.json')
        roots = [str(PurePosixPath(p).parent) for p in main if 'node_modules/' not in p]
        assets = [n for n in names if '/assets/' in n or n.startswith('src/assets/')]
        components = [n for n in names if n.endswith(('.tsx', '.jsx')) and '/node_modules/' not in n]
        bridge = candidates('campusfr/bridge-entry.tsx') or candidates('src/bridge/entry.tsx')
        catalog = {lang: candidates(f'content/{lang}.json') for lang in ('ru','fr','en')}
        result = {
            'source': zip_path.name,
            'project_roots': roots,
            'react_component_files': len(components),
            'media_assets': len(assets),
            'bridge_entry_candidates': bridge,
            'language_catalogues': catalog,
            'ready_to_package': bool(bridge and all(catalog.values())),
        }
        print(json.dumps(result, ensure_ascii=False, indent=2))
        if not result['ready_to_package']:
            print('AVIS : export Lovable brut. La création de l’adaptateur React, '
                  'des catalogues RU/FR/EN et la compilation restent nécessaires. '
                  'L’inspection n’installe aucun JS.', file=sys.stderr)

def extract_text_shape(catalog: dict) -> dict:
    result = {}
    for section, items in catalog.items():
        ensure(isinstance(section, str) and KEY_PATTERN.fullmatch(section) is not None and '__' not in section,
               'Section invalide : ' + str(section))
        ensure(isinstance(items, dict) and bool(items), 'Section doit être un objet non vide')
        shape = {}
        for key, value in items.items():
            ensure(isinstance(key, str) and KEY_PATTERN.fullmatch(key) is not None and '__' not in key,
                   'Clé invalide : ' + str(key))
            if isinstance(value, str):
                ensure(not (section == 'hero' and key == 'facts'), 'hero.facts doit être une liste de trois textes')
                ensure(len(value.encode('utf-8')) <= 5000, 'Texte trop long')
                shape[key] = 'str'
            elif isinstance(value, list) and 1 <= len(value) <= 20 and all(isinstance(x,str) for x in value):
                # Current Moodle BridgeSettings allows only hero.facts (exactly 3)
                # as a list in the header/hero pilot configuration.
                if section in ('header', 'hero'):
                    ensure(section == 'hero' and key == 'facts' and len(value) == 3,
                           'Tableau non pris en charge dans header/hero')
                ensure(all(len(x.encode('utf-8')) <= 5000 for x in value), 'Texte trop long')
                shape[key] = ['str'] * len(value)
            else:
                raise InvalidPackage('Valeur de catalogue invalide : ' + section + '.' + key)
        result[section] = shape
    return result

def validate_directory(base: Path) -> dict:
    """Validate a ready-to-serve, code-reviewed build, not arbitrary React source."""
    ensure(base.is_dir() and not base.is_symlink(), 'Répertoire package absent/symbolique')
    manifest_path = base/'manifest.json'
    ensure(manifest_path.is_file() and not manifest_path.is_symlink(), 'manifest.json absent')
    ensure(manifest_path.stat().st_size <= MAX_MANIFEST, 'Manifeste trop volumineux')
    manifest = json.loads(manifest_path.read_text(encoding='utf-8'))
    ensure(isinstance(manifest, dict), 'Manifeste non objet')
    designid = manifest.get('id')
    ensure(isinstance(designid, str) and ID_PATTERN.fullmatch(designid) is not None, 'ID de design incorrect')
    ensure(isinstance(manifest.get('version'), str) and VERSION_PATTERN.fullmatch(manifest['version']) is not None,
           'Version semver x.y.z requise')
    label = manifest.get('label', designid)
    ensure(isinstance(label,str) and 0 < len(label.encode('utf-8')) <= 150
           and not any(ord(x) < 32 or x in '<>' for x in label), 'Libellé de design invalide')
    for attr, rel in [('entry','dist/main.js'), ('css','dist/style.css'), ('content','content/ru.json')]:
        ensure(manifest.get(attr) == rel, f'Chemin {attr} non conforme')
    files = []
    total = 0
    for path in sorted(base.rglob('*')):
        ensure(not path.is_symlink(), 'Lien symbolique interdit : ' + str(path))
        if path.is_dir():
            continue
        ensure(path.is_file(), 'Fichier non régulier')
        rel = path.relative_to(base).as_posix()
        ensure(pathsafe(rel), 'Chemin suspect : ' + rel)
        valid = (rel == 'manifest.json'
                 or rel in {'content/ru.json','content/fr.json','content/en.json'}
                 or (rel.startswith('dist/') and path.suffix.lower() in ALLOWED))
        ensure(valid, 'Fichier interdit dans le package : ' + rel)
        size = path.stat().st_size
        ensure(size <= MAX_FILE, 'Fichier trop volumineux : ' + rel)
        total += size
        ensure(total <= MAX_TOTAL, 'Package trop volumineux')
        files.append((rel, path))
        ensure(len(files) <= MAX_FILES, 'Package contient trop de fichiers')
    mandatory = {'manifest.json','dist/main.js','dist/style.css','content/ru.json','content/fr.json','content/en.json'}
    ensure(mandatory <= {x[0] for x in files}, 'Artefact ou catalogue RU/FR/EN manquant')
    for lang in ('ru','fr','en'):
        ensure((base/'content'/f'{lang}.json').stat().st_size <= MAX_CATALOG, 'Catalogue trop volumineux')
    catalogs = {}
    for lang in ('ru','fr','en'):
        obj = json.loads((base/'content'/f'{lang}.json').read_text(encoding='utf-8'))
        ensure(isinstance(obj, dict) and bool(obj), 'Catalogue non objet ou vide : ' + lang)
        catalogs[lang] = obj
    shapes = {lang:extract_text_shape(obj) for lang,obj in catalogs.items()}
    ensure(shapes['ru'] == shapes['fr'] == shapes['en'], 'Clés/champs RU, FR et EN différents')
    count = sum((len(v) if isinstance(v,list) else 1) for section in shapes['ru'].values() for v in section.values())
    ensure(count <= 800, 'Trop de champs pour l’éditeur X2')
    entry = (base/'dist/main.js').read_text(encoding='utf-8')
    ensure('campusfr-showroom-bridge/v1' in entry and designid in entry and 'data-campusfr-bridge' in entry,
           'Le bundle ne déclare pas le contrat Bridge v1 ou le bon id ; adaptateur requis')
    return {'manifest':manifest, 'files':files, 'text_fields':count, 'bytes':total}

def package_dir(source: Path, output: Path) -> None:
    report = validate_directory(source)
    ensure(not output.exists(), 'Sortie déjà présente : ' + str(output))
    output.parent.mkdir(parents=True, exist_ok=True)
    designid = report['manifest']['id']
    with zipfile.ZipFile(output, 'x', compression=zipfile.ZIP_DEFLATED, compresslevel=8) as archive:
        for rel, path in report['files']:
            archive.write(path, f'designs/{designid}/{rel}')
    print('Package créé :', output)
    print('Identifiant :', designid, '| Textes :', report['text_fields'])
    print('SHA256 à contrôler/approuver :', hashlib.sha256(output.read_bytes()).hexdigest())
    print('ATTENTION : la compilation JS doit être revue avant déploiement ; ceci ne signe pas le code.')

def read_approved_zip(inputzip: Path, expectedsha: str) -> tuple[str,dict,dict[str,bytes]]:
    ensure(re.fullmatch(r'[0-9a-f]{64}', expectedsha) is not None, 'SHA256 attendu invalide')
    ensure(inputzip.is_file(), 'ZIP introuvable')
    ensure(hashlib.sha256(inputzip.read_bytes()).hexdigest() == expectedsha, 'SHA256 non conforme')
    with zipfile.ZipFile(inputzip) as archive:
        infos = [i for i in archive.infolist() if not i.is_dir()]
        ensure(len(infos) <= MAX_FILES, 'Trop de fichiers')
        ensure(len({i.filename for i in infos}) == len(infos), 'Noms dupliqués')
        ensure(sum(i.file_size for i in infos) <= MAX_TOTAL, 'ZIP trop volumineux')
        for info in infos:
            ensure(pathsafe(info.filename), 'Chemin ZIP dangereux')
            ensure(info.file_size <= MAX_FILE, 'Fichier ZIP trop volumineux')
            ensure(not stat.S_ISLNK(info.external_attr >> 16), 'Lien symbolique dans le ZIP')
        roots = {PurePosixPath(i.filename).parts[1] for i in infos
                 if len(PurePosixPath(i.filename).parts) >= 3 and PurePosixPath(i.filename).parts[0] == 'designs'}
        ensure(len(roots) == 1, 'ZIP doit contenir un unique design')
        designid = next(iter(roots))
        ensure(ID_PATTERN.fullmatch(designid) is not None, 'ID invalide')
        prefix = f'designs/{designid}/'
        ensure(all(i.filename.startswith(prefix) for i in infos), 'Fichiers supplémentaires interdits')
        content = {i.filename[len(prefix):]:archive.read(i) for i in infos}
    return designid, {'count':len(content)}, content

def install_dev(inputzip: Path, sha: str, replace: bool) -> None:
    ensure(os.geteuid() == 0, 'Installation DEV réservée à root')
    ensure(DEV_PLUGIN.is_dir() and (DEV_PLUGIN/'version.php').is_file(), 'Plugin Moodle DEV absent')
    import subprocess
    branch = subprocess.run(['git','-C',str(DEV_PLUGIN.parents[1]), 'branch','--show-current'],
                            capture_output=True, text=True, check=False)
    ensure(branch.returncode == 0 and branch.stdout.strip() == 'release/commerce-7.98-a1-launch',
           'Branche Moodle DEV inattendue')
    designid, _, content = read_approved_zip(inputzip, sha)
    targetroot = DEV_PLUGIN/'designs'
    target = targetroot/designid
    ensure(not target.is_symlink(), 'Répertoire design symbolique interdit')
    if target.exists():
        ensure(replace, 'Design existant : --replace explicite requis')
    staging = Path(tempfile.mkdtemp(prefix='.bridge_stage_', dir=targetroot))
    backup = None
    try:
        for rel,data in content.items():
            p = staging/rel
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_bytes(data)
            p.chmod(0o644)
        for p in staging.rglob('*'):
            if p.is_dir():
                p.chmod(0o755)
        staging.chmod(0o755)
        report = validate_directory(staging)
        ensure(report['manifest']['id'] == designid, 'ID manifeste différent du ZIP')
        if target.exists():
            oldversion = json.loads((target/'manifest.json').read_text(encoding='utf-8')).get('version')
            oldparts = tuple(int(x) for x in oldversion.split('.'))
            newparts = tuple(int(x) for x in report['manifest']['version'].split('.'))
            ensure(newparts > oldparts, 'La version du package doit augmenter')
            backup = Path('/tmp') / ('campusfr_bridge_' + designid + '_' + datetime.now(timezone.utc).strftime('%Y%m%d_%H%M%S'))
            ensure(not backup.exists(), 'Sauvegarde existante : réessaie')
            shutil.copytree(target, backup, symlinks=False)
        oldpath = targetroot / ('.bridge_old_' + designid + '_' + datetime.now(timezone.utc).strftime('%Y%m%d%H%M%S'))
        oldmoved = False
        if target.exists():
            os.rename(target, oldpath)
            oldmoved = True
        try:
            os.rename(staging, target)
        except Exception:
            if oldmoved and oldpath.exists():
                os.rename(oldpath, target)
            raise
        if oldmoved:
            shutil.rmtree(oldpath)
        print('OK DEV :', target)
        print('Version :', report['manifest']['version'])
        print('Sauvegarde de la version précédente :', backup or 'Aucune (nouveau design)')
        print('Pas de modification version.php ni de base SQL. Purger les caches DEV avant vérification.')
    finally:
        if staging.exists():
            shutil.rmtree(staging)

def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='command', required=True)
    inspectp = sub.add_parser('inspect', help='Analyser sans rien extraire un codebase ZIP brut de Lovable')
    inspectp.add_argument('zip', type=Path)
    packp = sub.add_parser('pack', help='Emballer un build Bridge compilé et revu')
    packp.add_argument('source', type=Path, help='Dossier contenant manifest.json, dist/, content/')
    packp.add_argument('output', type=Path)
    installp = sub.add_parser('install-dev', help='Installer sur DEV un ZIP approuvé, jamais PROD')
    installp.add_argument('zip', type=Path)
    installp.add_argument('--approve-sha256', required=True)
    installp.add_argument('--replace', action='store_true', help='Remplacer un design existant après sauvegarde')
    prep = sub.add_parser('prepare', help='X2.2 : préparer un export Lovable brut, sans installation ni npm')
    prep.add_argument('zip', type=Path)
    prep.add_argument('--id', required=True, help='ID stable du futur design : lettres minuscules, chiffres, _')
    prep.add_argument('--label', required=True, help='Nom affiché dans le CMS (après compilation et validation)')
    prep.add_argument('--output', required=True, type=Path, help='Workspace hors /var/www, nouveau dossier uniquement')
    args = parser.parse_args()
    try:
        if args.command == 'inspect':
            inspect_zip(args.zip)
        elif args.command == 'pack':
            package_dir(args.source, args.output)
        elif args.command == 'install-dev':
            install_dev(args.zip, args.approve_sha256, args.replace)
        elif args.command == 'prepare':
            from bridge_lovable_prepare import prepare_lovable
            report = prepare_lovable(args.zip, args.id, args.label, args.output)
            print('CampusFR X2.2 : workspace source créé :', args.output)
            print(json.dumps(report, ensure_ascii=False, indent=2))
            print('STOP : revue Commerce / langues / assets obligatoire avant pack ou installation DEV.')
    except (InvalidPackage, ValueError, OSError, json.JSONDecodeError, zipfile.BadZipFile) as err:
        print('STOP import Lovable :', err, file=sys.stderr)
        raise SystemExit(1)

if __name__ == '__main__':
    main()
