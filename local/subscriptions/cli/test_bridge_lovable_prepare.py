"""X2.2 standalone offline tests: no Moodle, no npm, no website/network access."""
from __future__ import annotations
import json
import os
from pathlib import Path
import stat
import subprocess
import sys
import tempfile
import unittest
import zipfile

from bridge_lovable_prepare import PrepareError, prepare_lovable

SRC_TSX = '''import React from 'react';
export default function Index() {return <main><h1>Привет CampusFR</h1><p>Bonjour tout le monde</p></main>}
'''
SRC_TANSTACK = '''import {createFileRoute} from '@tanstack/react-router';
export const Route = createFileRoute('/')({component: Index});
function Index() {return <main><h1>Привет !</h1></main>}
'''

class PrepareTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.root = Path(self.tmp.name)

    def tearDown(self):
        self.tmp.cleanup()

    def archive(self, files=None, prefix='', path='input.zip', mode=None):
        entries = files or {
            'package.json': json.dumps({'name': 'test', 'dependencies': {'react': '^19.0.0'}}),
            'src/App.tsx': SRC_TSX,
            'src/styles.css': '@import "tailwindcss"; :root{--color:red}',
            'src/assets/hero.jpg': b'image',
        }
        dest = self.root / path
        with zipfile.ZipFile(dest, 'w') as zf:
            for name, value in entries.items():
                info = zipfile.ZipInfo(prefix + name)
                if mode is not None and name.endswith('.tsx'):
                    info.external_attr = (mode << 16)
                zf.writestr(info, value)
        return dest

    def run_prepare(self, filename=None, dest='work', designid='mon_cours'):
        return prepare_lovable(filename or self.archive(), designid, 'Un cours', self.root / dest)

    def test_standard_spa_workspace_and_manifest(self):
        data = self.run_prepare()
        self.assertEqual(data['kind'], 'spa')
        self.assertEqual(data['status'], 'PREPARED_SOURCE_ONLY')
        workspace = self.root/'work/source'
        manifest = json.loads((workspace/'manifest.campusfr.json').read_text())
        self.assertEqual(manifest['id'], 'mon_cours')
        self.assertTrue((workspace/'src/bridge/campusfr-entry.tsx').exists())
        self.assertEqual((workspace/'src/assets/hero.jpg').read_bytes(), b'image')
        package = json.loads((workspace/'package.json').read_text())
        self.assertEqual(package['devDependencies']['vite'], '7.3.7')
        self.assertIn('CAMPUSFR_BRIDGE_PREVIEW=1', package['scripts']['campusfr:build-preview'])
        self.assertFalse((self.root/'work/ready').exists())

    def test_tanstack_root_and_preserve_source(self):
        data = self.run_prepare(self.archive({
            'package.json': json.dumps({'dependencies': {'react': '*'}, 'devDependencies':
                                       {'@lovable.dev/vite-tanstack-config':'2.26.5', 'vite':'8.1.5'},
                                       'overrides':{'rolldown':'1.2.1'}}),
            'src/routes/index.tsx': SRC_TANSTACK,
            'src/styles.css': ':root{--x:1}',
        }, prefix='lovable-project/'))
        self.assertEqual(data['kind'], 'tanstack')
        path = self.root/'work/source/src/routes/index.tsx'
        self.assertEqual(path.read_text(), SRC_TANSTACK)
        pkg = json.loads((self.root/'work/source/package.json').read_text())
        self.assertNotIn('@lovable.dev/vite-tanstack-config', pkg['devDependencies'])
        self.assertNotIn('overrides', pkg)

    def test_env_files_excluded(self):
        inp = self.archive({'package.json':'{"dependencies":{"react":"*"}}',
                            'src/App.tsx':SRC_TSX, 'src/styles.css':'@import "tailwindcss";',
                            '.env':'SECRET=private', '.env.production':'SECRET=prod',
                            '.npmrc':'_authToken=secret', 'src/extra.env.local':'SECRETS'}, path='secrets.zip')
        report = self.run_prepare(inp)
        src = self.root/'work/source'
        self.assertFalse((src/'.env').exists())
        self.assertFalse((src/'.env.production').exists())
        self.assertFalse((src/'.npmrc').exists())
        self.assertFalse((src/'src/extra.env.local').exists())
        self.assertGreaterEqual(len(report['omittedFiles']),3)

    def test_refuse_zip_path_traversal(self):
        with self.assertRaisesRegex(PrepareError, 'ZIP dangereux'):
            self.run_prepare(self.archive({'../foo':'bar', 'package.json':'{}'}))

    def test_refuse_zip_absolute_path(self):
        with self.assertRaisesRegex(PrepareError, 'ZIP dangereux'):
            self.run_prepare(self.archive({'/tmp/foo':'bar', 'package.json':'{}'}))

    def test_refuse_zip_symlink(self):
        inp = self.archive(mode=stat.S_IFLNK | 0o777)
        with self.assertRaisesRegex(PrepareError, 'symbolique'):
            self.run_prepare(inp)

    def test_refuse_ambiguous_multiple_projects(self):
        inp = self.archive({'pkg1/package.json':'{}', 'pkg2/package.json':'{}',
                            'pkg1/src/App.tsx':SRC_TSX, 'pkg1/src/styles.css':'x'})
        with self.assertRaisesRegex(PrepareError, 'exactement un package.json'):
            self.run_prepare(inp)

    def test_refuse_duplicate_archive_path(self):
        z = self.root/'duplicate.zip'
        with zipfile.ZipFile(z, 'w') as f:
            import warnings
            with warnings.catch_warnings():
                warnings.simplefilter('ignore', UserWarning)
                f.writestr('package.json', '{}')
                f.writestr('package.json', '{}')
        with self.assertRaisesRegex(PrepareError, 'doublon'):
            self.run_prepare(z)

    def test_refuse_overwrite_existing_workspace(self):
        target = self.root/'work'; target.mkdir()
        with self.assertRaisesRegex(PrepareError, 'existe déjà'):
            self.run_prepare()

    def test_refuse_invalid_designid(self):
        with self.assertRaisesRegex(PrepareError, 'ID invalide'):
            self.run_prepare(designid='../dangerous')

    def test_no_transformation_without_execution(self):
        self.run_prepare()
        self.assertEqual((self.root/'work/source/src/App.tsx').read_text(), SRC_TSX)
        self.assertFalse((self.root/'work/source/content/ru.json').exists())

    def test_node_extract_if_typescript_available(self):
        import shutil
        if not shutil.which('node'):
            self.skipTest('node unavailable: build-stage test run on isolated builder')
        self.run_prepare()
        cwd = self.root/'work/source'
        env = dict(os.environ)
        if Path('/opt/nvm/versions/node/v22.16.0/lib/node_modules/typescript').exists():
            env['NODE_PATH'] = '/opt/nvm/versions/node/v22.16.0/lib/node_modules'
        # No npm or arbitrary JS app executed: only the audited TS AST transformer.
        result = subprocess.run(['node','scripts/campusfr-texts.cjs'],cwd=cwd,env=env,
                                capture_output=True,text=True)
        if 'Cannot find module' in result.stderr and 'typescript' in result.stderr:
            self.skipTest('typescript dependency not installed for isolated test')
        self.assertEqual(result.returncode, 0, result.stdout+result.stderr)
        src = (cwd/'src/App.tsx').read_text()
        self.assertIn('<BridgeText id=', src)
        data = json.loads((cwd/'content/ru.json').read_text())
        self.assertEqual(data, json.loads((cwd/'content/fr.json').read_text()))
        self.assertTrue(len(data['page']) >= 2)
        self.assertFalse((cwd/'campusfr-approval.json').exists())

if __name__ == '__main__':
    unittest.main()
