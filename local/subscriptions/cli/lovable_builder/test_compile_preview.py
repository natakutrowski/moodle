"""Offline, non-executable security tests for the rootless preview compiler."""
import importlib.util
import json
from pathlib import Path
import stat
import tempfile
import unittest
import zipfile

modpath = Path(__file__).with_name('compile_preview.py')
spec = importlib.util.spec_from_file_location('compiler', modpath)
compiler = importlib.util.module_from_spec(spec)
spec.loader.exec_module(compiler)


class CompilePreviewTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.root = Path(self.temporary.name)
        self.design = 'sample_campusfr'

    def tearDown(self):
        self.temporary.cleanup()

    def make_archive(self, extra=None):
        archive = self.root/'test.zip'
        report = {'designId': self.design, 'approved': False, 'status': 'PREPARED_SOURCE_ONLY'}
        files = {
            'campusfr-inspection.json': json.dumps(report),
            'source/manifest.campusfr.json': json.dumps({'id': self.design}),
            'source/campusfr-prep.json': json.dumps({'designId': self.design}),
            'source/package.json': json.dumps({'scripts': {
                'campusfr:extract': 'node scripts/campusfr-texts.cjs',
                'campusfr:build-preview': 'CAMPUSFR_BRIDGE_PREVIEW=1 vite build --config vite.bridge.config.ts',
            }}),
            'source/scripts/campusfr-texts.cjs': 'test',
            'source/scripts/campusfr-finalize.mjs': 'test',
            'source/src/bridge/campusfr-entry.tsx': 'test',
            'source/vite.bridge.config.ts': 'test',
        }
        files.update(extra or {})
        with zipfile.ZipFile(archive,'w') as zipf:
            for name, data in files.items():
                zipf.writestr(name,data)
        return archive

    def test_extract_valid_prepared_without_execution(self):
        inp = self.make_archive()
        dest = self.root/'work'; dest.mkdir()
        compiler.unpack_prepared(inp, dest, self.design)
        self.assertTrue((dest/'source/vite.bridge.config.ts').exists())
        self.assertFalse((dest/'source/node_modules').exists())

    def test_refuse_zip_traversal(self):
        inp = self.make_archive({'source/../../outside': 'oops'})
        dest = self.root/'work'; dest.mkdir()
        with self.assertRaisesRegex(RuntimeError,'dangereux'):
            compiler.unpack_prepared(inp,dest,self.design)

    def test_refuse_zip_symlink(self):
        inp = self.make_archive()
        with zipfile.ZipFile(inp,'a') as zipf:
            info = zipfile.ZipInfo('source/link')
            info.create_system = 3
            info.external_attr = ((stat.S_IFLNK | 0o777) << 16)
            zipf.writestr(info,'/etc/passwd')
        dest = self.root/'work'; dest.mkdir()
        with self.assertRaisesRegex(RuntimeError,'symboliques'):
            compiler.unpack_prepared(inp,dest,self.design)

    def test_refuse_injected_scripts(self):
        inp = self.make_archive({'source/package.json':json.dumps({'scripts': {
            'campusfr:extract':'evil.sh', 'campusfr:build-preview':'vite build --config vite.bridge.config.ts'
        }})})
        dest=self.root/'work'; dest.mkdir()
        with self.assertRaisesRegex(RuntimeError,'non conformes'):
            compiler.unpack_prepared(inp,dest,self.design)

    def test_refuse_unapproved_or_different_id(self):
        for report in ({'designId':self.design,'approved':True,'status':'PREPARED_SOURCE_ONLY'},
                       {'designId':'another','approved':False,'status':'PREPARED_SOURCE_ONLY'}):
            inp = self.make_archive({'campusfr-inspection.json': json.dumps(report)})
            dest=self.root/'work'; dest.mkdir(exist_ok=True)
            with self.assertRaisesRegex(RuntimeError,'non conforme'):
                compiler.unpack_prepared(inp,dest,self.design)

    def test_runtime_restricts_network_and_permissions(self):
        builder = self.root/'build_in_container.sh'; builder.write_text('test')
        cmd = compiler.container_command(self.root,self.root,builder)
        self.assertIn('--network=none',cmd)
        self.assertIn('--read-only',cmd)
        self.assertIn('--cap-drop=ALL',cmd)
        self.assertIn('--security-opt=no-new-privileges',cmd)
        self.assertIn('--pull=never',cmd)
        self.assertIn('--userns=keep-id',cmd)
        self.assertIn('--ulimit=nofile=8192:8192', cmd)
        self.assertNotIn('--ulimit=nofile=1024:1024', cmd)
        self.assertNotIn('docker',cmd)
        self.assertEqual(cmd[0],'/usr/bin/podman')


    def test_offline_dependencies_are_immutable_image_snapshot(self):
        builder = Path(__file__).with_name('build_in_container.sh').read_text(encoding='utf-8')
        source = modpath.read_text(encoding='utf-8')
        self.assertIn('cp -R /opt/campusfr-build-cache/node_modules/. node_modules/', builder)
        self.assertNotIn('npm install --offline', builder)
        self.assertNotIn('cp -a /opt/npm-cache', builder)
        self.assertIn("IMAGE = 'localhost/campusfr-lovable-builder:3'", source)
        self.assertIn("'--network=none'", source)
        self.assertIn("'--memory=2g'", source)
        self.assertIn("'--cpus=2'", source)
        self.assertIn("'--pids-limit=128'", source)
        self.assertIn("'--tmpfs=/tmp:rw,nosuid,noexec,size=384m'", source)

    def test_reject_bundled_modules_in_untrusted_prepared_zip(self):
        inp = self.make_archive({'source/node_modules/react/index.js': 'untrusted'})
        dest = self.root/'work'; dest.mkdir()
        with self.assertRaisesRegex(RuntimeError,'node_modules'):
            compiler.unpack_prepared(inp,dest,self.design)

    def test_draft_contains_no_root_installable_manifest(self):
        folder=self.root/'result'; (folder/'dist').mkdir(parents=True)
        (folder/'content').mkdir()
        (folder/'dist/main.js').write_text('const x=1')
        (folder/'dist/style.css').write_text('body{}')
        (folder/'manifest.json').write_text(json.dumps({'id':self.design}))
        for lang in ('ru','fr','en'):
            (folder/'content'/f'{lang}.json').write_text('{}')
        report=self.root/'report.json'; report.write_text('{}')
        output=self.root/'draft.zip'
        compiler.archive_draft(folder,output,self.design,report)
        with zipfile.ZipFile(output) as zf:
            names=zf.namelist()
        self.assertIn('draft/'+self.design+'/dist/main.js',names)
        self.assertIn('NON_PUBLIABLE.txt',names)
        self.assertNotIn('manifest.json',names)

    def test_refuse_overwriting_existing_zip(self):
        output=self.root/'draft.zip'; output.write_bytes(b'EXISTING')
        folder=self.root/'result'; folder.mkdir()
        report=self.root/'report.json'; report.write_text('{}')
        with self.assertRaisesRegex(RuntimeError,'déjà présent'):
            compiler.archive_draft(folder,output,self.design,report)
        self.assertEqual(output.read_bytes(),b'EXISTING')

if __name__ == '__main__':
    unittest.main(verbosity=2)
