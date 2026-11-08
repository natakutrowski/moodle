#!/usr/bin/env python3
"""X2.3A: execute reviewed CampusFR draft compilation ONLY in rootless Podman.

This script is invoked by a Moodle adhoc task, never by a shell with user input.
It does not install JS into the Moodle site.  No network access is allowed for JS.
"""
from __future__ import annotations
import json
import os
import re
import shutil
import stat
import subprocess
import sys
import tempfile
import time
from pathlib import Path
import zipfile

IMAGE = 'localhost/campusfr-lovable-builder:3'
# Dedicated private on-disk workspace: npm cache is larger than the 384 MiB /tmp tmpfs.
BUILD_HOME = Path('/var/lib/campusfr-builder')
ID = re.compile(r'^[a-z][a-z0-9_]{2,47}$')
MAX_FILES = 3500
MAX_BYTES = 320 * 1024 * 1024
MAX_ITEM = 35 * 1024 * 1024
MAX_ARCHIVE = 60 * 1024 * 1024
MAX_LOG = 150 * 1024


def fail(message: str) -> None:
    raise RuntimeError(message)


def safe_entry(name: str) -> bool:
    return (bool(name) and not name.startswith('/') and '\\' not in name
            and all(x not in ('', '.', '..') for x in name.rstrip('/').split('/')))


def unpack_prepared(archive: Path, stage: Path, design: str) -> None:
    if not archive.is_file() or archive.is_symlink() or archive.stat().st_size > MAX_ARCHIVE:
        fail('Archive préparée absente, symbolique ou trop grande.')
    with zipfile.ZipFile(archive) as zf:
        entries = zf.infolist()
        if len(entries) > MAX_FILES:
            fail('Nombre de fichiers trop élevé.')
        names = [z.filename.rstrip('/') for z in entries]
        if len(set(names)) != len(names):
            fail('Archive avec noms en double.')
        if not all(safe_entry(z.filename) for z in entries):
            fail('Chemin dangereux dans le ZIP préparé.')
        total = 0
        for entry in entries:
            if stat.S_ISLNK(entry.external_attr >> 16):
                fail('Liens symboliques interdits dans le ZIP.')
            if entry.is_dir():
                continue
            total += entry.file_size
            if entry.file_size > MAX_ITEM or total > MAX_BYTES:
                fail('ZIP préparé trop volumineux.')
            if entry.filename.startswith('source/node_modules/') or entry.filename.rstrip('/') == 'source/node_modules':
                fail('Dépendances intégrées au projet interdites : node_modules doit venir de l’image approuvée.')
            if not (entry.filename.startswith('source/') or entry.filename in
                    {'campusfr-inspection.json', 'README_CAMPUSFR_X22.md'}):
                fail('Fichier inattendu dans le ZIP préparé.')
            path = stage / entry.filename
            path.parent.mkdir(parents=True, exist_ok=True)
            with zf.open(entry, 'r') as src, path.open('wb') as dst:
                shutil.copyfileobj(src, dst, 64 * 1024)
            path.chmod(0o644)
    reportfile = stage / 'campusfr-inspection.json'
    manifestfile = stage / 'source/manifest.campusfr.json'
    configfile = stage / 'source/campusfr-prep.json'
    if not all(x.is_file() for x in (reportfile, manifestfile, configfile)):
        fail('Rapport ou manifeste CampusFR manquant.')
    report = json.loads(reportfile.read_text(encoding='utf-8'))
    manifest = json.loads(manifestfile.read_text(encoding='utf-8'))
    config = json.loads(configfile.read_text(encoding='utf-8'))
    if (report.get('status') != 'PREPARED_SOURCE_ONLY' or report.get('approved') is not False or
            report.get('designId') != design or manifest.get('id') != design or
            config.get('designId') != design):
        fail('Projet préparé non conforme ou différent de son identifiant.')
    package = json.loads((stage / 'source/package.json').read_text(encoding='utf-8'))
    scripts = package.get('scripts', {})
    if (not isinstance(scripts, dict) or
            scripts.get('campusfr:extract') != 'node scripts/campusfr-texts.cjs' or
            'vite build --config vite.bridge.config.ts' not in scripts.get('campusfr:build-preview', '')):
        fail('Scripts CampusFR non conformes ; réimporter le ZIP Lovable original.')
    required = [
        'source/scripts/campusfr-texts.cjs', 'source/scripts/campusfr-finalize.mjs',
        'source/src/bridge/campusfr-entry.tsx', 'source/vite.bridge.config.ts',
    ]
    for filename in required:
        if not (stage / filename).is_file():
            fail('Modèle Bridge manquant : ' + filename)


def container_command(work: Path, result: Path, builder: Path) -> list[str]:
    if not builder.is_file() or builder.is_symlink():
        fail('Script de build fiable manquant.')
    return [
        '/usr/bin/podman', 'run', '--rm', '--pull=never', '--network=none',
        '--read-only', '--userns=keep-id', '--cap-drop=ALL',
        '--security-opt=no-new-privileges', '--pids-limit=128',
        '--memory=2g', '--cpus=2', '--ulimit=nofile=8192:8192',
        '--tmpfs=/tmp:rw,nosuid,noexec,size=384m',
        f'--volume={work}:/work:rw',
        f'--volume={result}:/result:rw',
        f'--volume={builder}:/usr/local/bin/campusfr-build:ro',
        '--workdir=/work/source',
        IMAGE, '/bin/bash', '/usr/local/bin/campusfr-build',
    ]


def run_container(command: list[str]) -> None:
    print('ENVIRONNEMENT : conteneur Podman rootless, réseau désactivé, droits réduits.', flush=True)
    print('COMMANDES DU MOTEUR (options de sécurité conservées) :', flush=True)
    print(' '.join(command), flush=True)
    process = subprocess.Popen(command, stdin=subprocess.DEVNULL,
                               stdout=subprocess.PIPE, stderr=subprocess.STDOUT,
                               text=True, bufsize=1)
    printed = 0
    started = time.monotonic()
    try:
        assert process.stdout is not None
        for line in process.stdout:
            if printed < MAX_LOG:
                remaining = MAX_LOG - printed
                rendered = line[:remaining]
                print(rendered, end='', flush=True)
                printed += len(rendered)
                if printed >= MAX_LOG:
                    print('\n[Journal tronqué : limite de sécurité atteinte]', flush=True)
            if time.monotonic() - started > 150:
                process.kill()
                fail('Compilation interrompue après 150 secondes.')
        code = process.wait(timeout=10)
        if code != 0:
            fail(f'Conteneur terminé avec le code {code}. Vérifier l’image et le cache npm du moteur.')
    finally:
        if process.poll() is None:
            process.kill()
            process.wait()


def archive_draft(result: Path, output: Path, design: str, report: Path) -> None:
    if output.exists() or output.is_symlink():
        fail('ZIP de sortie déjà présent : aucun écrasement autorisé.')
    expected = result / 'manifest.json'
    js = result / 'dist/main.js'
    css = result / 'dist/style.css'
    if not all(path.is_file() and not path.is_symlink() for path in (expected, js, css)):
        fail('Compilation incomplète : manifeste, main.js ou style.css absent.')
    manifest = json.loads(expected.read_text(encoding='utf-8'))
    if manifest.get('id') != design:
        fail('Identifiant du build différent du projet demandé.')
    for language in ('ru', 'fr', 'en'):
        if not (result / 'content' / f'{language}.json').is_file():
            fail('Catalogue ' + language + ' manquant.')
    if output.exists() or output.is_symlink():
        fail('ZIP de sortie déjà présent : aucun écrasement autorisé.')
    written = 0
    files = sorted(p for p in result.rglob('*') if p.is_file())
    if len(files) > MAX_FILES:
        fail('Trop de fichiers compilés.')
    if any(p.is_symlink() for p in result.rglob('*')):
        fail('Résultat contenant un lien symbolique.')
    with zipfile.ZipFile(output, 'x', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        for path in files:
            written += path.stat().st_size
            if written > MAX_BYTES or path.stat().st_size > MAX_ITEM:
                fail('Sortie compilée trop volumineuse.')
            zf.write(path, 'draft/' + design + '/' + path.relative_to(result).as_posix())
        zf.write(report, 'campusfr-inspection.json')
        zf.writestr('NON_PUBLIABLE.txt', 'Aperçu CampusFR, génération isolée. Commerce et traductions non validés.\n')
    output.chmod(0o600)


def main() -> int:
    if len(sys.argv) != 4:
        fail('Usage : compile_preview.py WORKSPACE_ZIP OUTPUT_ZIP ID')
    archive, output = Path(sys.argv[1]), Path(sys.argv[2])
    design = sys.argv[3]
    if not ID.fullmatch(design):
        fail('Identifiant de projet invalide.')
    if os.geteuid() == 0:
        fail('Interdit : exécuter Podman rootful en tant que root.')
    if not Path('/usr/bin/podman').is_file():
        fail('Moteur Podman absent. Une configuration initiale du serveur est nécessaire.')
    if output.exists() or output.is_symlink():
        fail('Un résultat existe déjà pour ce projet.')
    if archive.resolve().parent != output.resolve().parent:
        fail('La sortie doit rester dans le même répertoire privé que le ZIP source.')
    builder = Path(__file__).with_name('build_in_container.sh').resolve()
    # Never expand the container's /tmp tmpfs or memory limits to fit npm's cache.
    # Use a per-build folder on disk, owned by the dedicated unprivileged user,
    # mounted only into this one network-disabled container and removed afterward.
    homestat = BUILD_HOME.lstat()
    if (not stat.S_ISDIR(homestat.st_mode) or homestat.st_uid != os.geteuid()
            or homestat.st_mode & 0o022):
        fail('Répertoire privé du builder non conforme.')
    if shutil.disk_usage(BUILD_HOME).free < 3 * 1024 * 1024 * 1024:
        fail('Espace disque insuffisant pour une compilation isolée.')
    with tempfile.TemporaryDirectory(prefix='campusfr_lovable_build_', dir=BUILD_HOME) as temp:
        tempdir = Path(temp)
        stage, result = tempdir/'work', tempdir/'result'
        stage.mkdir(mode=0o700)
        result.mkdir(mode=0o700)
        print('ÉTAPE 1/4 : Vérification et extraction sûre du projet préparé.', flush=True)
        unpack_prepared(archive, stage, design)
        print('ÉTAPE 2/4 : Compilation confinée. Le moteur ne peut pas accéder à Moodle.', flush=True)
        run_container(container_command(stage, result, builder))
        print('ÉTAPE 3/4 : Contrôle des fichiers compilés.', flush=True)
        archive_draft(result, output, design, stage / 'campusfr-inspection.json')
        print('ÉTAPE 4/4 : Aperçu ZIP produit. Aucun showroom, paiement ni publication créé.', flush=True)
    return 0


if __name__ == '__main__':
    try:
        sys.exit(main())
    except Exception as error:
        print('STOP X2.3 : ' + str(error), file=sys.stderr, flush=True)
        sys.exit(1)
