/* CampusFR X2.2: conservative TypeScript AST extraction of static JSX text.
 * Never guesses prices or rewrites strings inside expressions or JSX props.
 * Run only on a reviewed source copy in an isolated workspace.
 */
'use strict';
const ts = require('typescript');
const fs = require('node:fs');
const path = require('node:path');
const config = JSON.parse(fs.readFileSync('campusfr-prep.json', 'utf8'));
const pathname = path.resolve(config.page);
const backup = pathname + '.campusfr-original';
const original = fs.existsSync(backup) ? fs.readFileSync(backup, 'utf8') : fs.readFileSync(pathname, 'utf8');
const sf = ts.createSourceFile(config.page, original, ts.ScriptTarget.Latest, true,
    pathname.endsWith('.jsx') ? ts.ScriptKind.JSX : ts.ScriptKind.TSX);
if (sf.parseDiagnostics.length) {
  throw new Error('TSX parser: ' + sf.parseDiagnostics.map(x => String(x.messageText)).join('; '));
}
const changes = [];
const catalog = {};
const sectionCounters = {};
const manualProps = [];
const blockedTags = new Set(['script', 'style', 'code', 'pre', 'svg', 'title', 'option', 'select', 'textarea']);
let jsxCount = 0;
function add(start, end, text) { changes.push({start, end, text}); }
function functionSection(node) {
  let p = node.parent;
  while (p) {
    if (ts.isFunctionDeclaration(p) && p.name) return p.name.text;
    if (ts.isVariableDeclaration(p) && ts.isIdentifier(p.name)) return p.name.text;
    if (ts.isMethodDeclaration(p) && p.name && ts.isIdentifier(p.name)) return p.name.text;
    p = p.parent;
  }
  return 'misc';
}
function sanitize(name) {
  return String(name).replace(/([a-z])([A-Z])/g, '$1_$2').replace(/[^a-z0-9_]/gi,'_')
      .replace(/_+/g,'_').replace(/^_|_$/g,'').toLowerCase().slice(0,36) || 'misc';
}
function inBlockedMarkup(node) {
  let p = node.parent;
  while (p) {
    if (ts.isJsxElement(p) && blockedTags.has(p.openingElement.tagName.getText(sf).toLowerCase())) return true;
    p = p.parent;
  }
  return false;
}
function decodeEntities(text) {
  return text.replace(/&(nbsp|amp|lt|gt|quot|apos);/g, (_, entity) =>
    ({nbsp:'\u00a0', amp:'&', lt:'<', gt:'>', quot:'"', apos:"'"})[entity]);
}
function scan(n) {
  if (config.kind === 'tanstack' && ts.isVariableStatement(n) &&
      n.declarationList.declarations.some(d => d.name.getText(sf) === 'Route' &&
          d.initializer?.getText(sf).includes('createFileRoute('))) return;
  if (ts.isJsxText(n)) {
    const raw = n.getText(sf);
    const normalized = decodeEntities(raw.replace(/\s+/g,' '));
    const text = normalized.trim();

    // Plain JSX text only. No AST guesses about interpolated strings, links or dynamic code.
    if (text.length > 1 && text.length < 2500 && /\p{L}/u.test(text) && !inBlockedMarkup(n)) {
      const section = sanitize(functionSection(n));
      const index = (sectionCounters[section] || 0) + 1;
      sectionCounters[section] = index;
      const key = `${section}_${String(index).padStart(3,'0')}`;
      const fullText = (normalized.startsWith(' ') ? ' ' : '') + text +
          (normalized.endsWith(' ') ? ' ' : '');
      catalog[key] = fullText;
      add(n.getStart(sf), n.getEnd(), `<BridgeText id="${key}" fallback={${JSON.stringify(fullText)}} />`);
      jsxCount++;
    }
  }
  if (ts.isJsxAttribute(n) && n.initializer && ts.isStringLiteral(n.initializer)) {
    const attr = n.name.getText(sf);
    if (['alt','placeholder','title','aria-label','aria-description'].includes(attr)) {
      manualProps.push({attribute:attr, value:n.initializer.text.slice(0,150), line:sf.getLineAndCharacterOfPosition(n.pos).line+1});
    }
  }
  ts.forEachChild(n, scan);
}
scan(sf);
if (!jsxCount) throw new Error('Aucun texte JSX statique détecté : conversion manuelle nécessaire.');
if (jsxCount > 800) throw new Error('Plus de 800 champs: limite CMS dépassée, découper la page.');
if (config.kind === 'spa') {
  const hasDefault = sf.statements.some(statement => ts.isExportAssignment(statement) ||
     (statement.modifiers && statement.modifiers.some(m => m.kind === ts.SyntaxKind.DefaultKeyword)));
  if (!hasDefault) throw new Error('Page SPA sans export default : adaptation manuelle requise');
}
if (config.kind === 'tanstack') {
  let routeFound = 0;
  let pageFound = false;
  for (const statement of sf.statements) {
    if (ts.isImportDeclaration(statement) &&
        statement.moduleSpecifier.text === '@tanstack/react-router' &&
        statement.importClause?.namedBindings?.getText(sf).includes('createFileRoute')) {
      const named = statement.importClause.namedBindings;
      if (!ts.isNamedImports(named) || named.elements.length !== 1 ||
          named.elements[0].name.text !== 'createFileRoute') {
        throw new Error('Import route complexe non automatisé (createFileRoute partagé)');
      }
      add(statement.getFullStart(), statement.end, '');
    }
    if (ts.isVariableStatement(statement)) {
      const declarations = statement.declarationList.declarations;
      if (declarations.length === 1 && declarations[0].name.getText(sf) === 'Route' &&
          declarations[0].initializer?.getText(sf).includes('createFileRoute(')) {
        routeFound++;
        add(statement.getFullStart(), statement.end, '');
      }
    }
    if (ts.isFunctionDeclaration(statement) && statement.name?.text === 'Index') pageFound = true;
  }
  if (routeFound !== 1 || !pageFound) {
    throw new Error('Route TanStack non standard : conversion contrôlée manuellement nécessaire');
  }
  // Existing route exports 'Route', not the actual React Index component.
  add(original.length, original.length, '\nexport default Index;\n');
}
const relativeToBridge = path.relative(path.dirname(pathname), path.resolve('src/bridge/campusfr-i18n'))
    .replace(/\\/g, '/');
const modulePath = relativeToBridge.startsWith('.') ? relativeToBridge : './' + relativeToBridge;
add(0, 0, `import {BridgeText} from ${JSON.stringify(modulePath)};\n`);
changes.sort((a,b) => b.start - a.start || b.end - a.end);
let transformed = original;
let prevStart = original.length + 1;
for (const change of changes) {
  if (change.end > prevStart) throw new Error('Modifications AST chevauchantes');
  transformed = transformed.slice(0, change.start) + change.text + transformed.slice(change.end);
  prevStart = change.start;
}
if (!fs.existsSync(backup)) fs.writeFileSync(backup, original);
fs.writeFileSync(pathname, transformed);
fs.mkdirSync('content', {recursive:true});
const contents = {page:catalog};
for (const lang of ['ru','fr','en']) {
  const out = path.join('content', lang + '.json');
  // Never overwrite a human-edited translation without explicit reconciliation.
  if (lang !== 'ru' && fs.existsSync(out)) {
    const previous = JSON.parse(fs.readFileSync(out, 'utf8'));
    if (previous?.page && Object.keys(previous.page).join('|') === Object.keys(catalog).join('|')) {
      // preserve translations for unchanged source keys, but warn on changes
      for (const key of Object.keys(catalog)) if (typeof previous.page[key] === 'string') {
        contents.page[key] = catalog[key];
      }
      fs.writeFileSync(out, JSON.stringify(previous, null, 2) + '\n');
      continue;
    }
    throw new Error(`Catalogue ${lang} existant différent : réconcilier manuellement avant extraction`);
  }
  fs.writeFileSync(out, JSON.stringify(contents, null, 2) + '\n');
}
const report = {
  designId: config.designId, sourcePage: config.page,
  editableJsxTexts: jsxCount, manualJsxAttributes: manualProps,
  translationStatus: 'FR/EN INITIALIZED WITH RU - NOT TRANSLATED',
  commerceStatus: 'NOT CONNECTED / FORMS BLOCKED IN DRAFT',
  caveats: [
    'Dynamic strings, JavaScript arrays and React props are NOT automatically converted',
    'Any checkout, donation, currency or customer entitlements must use the Commerce payload',
    'Inspect CSS, media asset URLs, portals, mobile and modal elements in the actual preview'
  ]
};
fs.writeFileSync('campusfr-report.json', JSON.stringify(report, null, 2) + '\n');
console.log(`CampusFR X2.2: ${jsxCount} JSX texts generated as editable CMS entries`);
console.log(`Remaining manual attributes: ${manualProps.length}; see campusfr-report.json`);
console.log('No payment integration, no translations and no production approval were performed.');
