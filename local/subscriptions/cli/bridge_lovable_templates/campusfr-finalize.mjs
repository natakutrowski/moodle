/** Place the generated catalogs + manifest alongside a Vite build. */
import {readFileSync, writeFileSync, mkdirSync, copyFileSync, existsSync, readdirSync, renameSync} from 'node:fs';
import {resolve, join} from 'node:path';
const manifest = JSON.parse(readFileSync('manifest.campusfr.json', 'utf8'));
const base = resolve('..', process.env.CAMPUSFR_BRIDGE_PREVIEW === '1' ? 'draft' : 'ready', manifest.id);
const dist = join(base, 'dist');
mkdirSync(join(base, 'content'), {recursive:true});
copyFileSync('manifest.campusfr.json', join(base, 'manifest.json'));
for (const lang of ['ru', 'fr', 'en']) {
  if (!existsSync(join('content', lang + '.json'))) throw new Error('Run campusfr:extract first');
  copyFileSync(join('content', lang + '.json'), join(base, 'content', lang + '.json'));
}
const target = join(dist, 'style.css');
if (!existsSync(target)) {
  const assets = join(dist, 'assets');
  const css = existsSync(assets) ? readdirSync(assets).filter(name => /^style-[\w-]+\.css$/.test(name)) : [];
  if (css.length !== 1) throw new Error('Compiled stylesheet not uniquely identifiable');
  renameSync(join(assets, css[0]), target);
}
let stylesheet = readFileSync(target, 'utf8');
const cssPath = existsSync('src/styles.css') ? 'src/styles.css' : 'src/index.css';
const original = readFileSync(cssPath, 'utf8');
const variables = [...original.matchAll(/:root\s*\{([^{}]*)\}/g)].map(match => ':host{' + match[1] + '}').join('\n');
// Bridge DOM uses ShadowRoot: :root tokens from Lovable are remapped to :host.
stylesheet = variables + '\n:host{display:block}\n' + stylesheet;
writeFileSync(target, stylesheet);
console.log('CampusFR build ready (REVIEW REQUIRED):', base);
