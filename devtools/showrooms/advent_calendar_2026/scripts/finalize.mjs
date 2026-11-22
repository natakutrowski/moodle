import {readFileSync,writeFileSync,readdirSync,renameSync,existsSync} from 'node:fs';
import {join} from 'node:path';
const dist='dist',css=join(dist,'style.css');
if(!existsSync(css)){
 const found=readdirSync(dist).filter(n=>n.endsWith('.css'));
 if(found.length!==1)throw new Error('Expected exactly one compiled stylesheet');
 renameSync(join(dist,found[0]),css);
}
const file=readFileSync(css,'utf8');
// React lives inside a ShadowRoot; :root must resolve to the component host.
// The complete source CSS (including two :root blocks) must be mapped, not just one.
const overrides=readFileSync(join('scripts','bridge-visual-overrides.css'),'utf8');
if(!overrides.includes('CAMPUSFR_ADVENT_BRIDGE_VISUAL_V4'))throw new Error('Missing V4 visual CSS marker');
const updated=':host{display:block;width:100%;min-height:100%;font-family:"Nunito",system-ui,sans-serif;line-height:1.5;color-scheme:dark;background:var(--night-deep);color:var(--night-foreground)}\n'+file.replace(/:root\b/g,':host')+'\n'+overrides;
writeFileSync(css,updated);
const js=readFileSync(join(dist,'main.js'),'utf8');
for(const text of ['campusfr-showroom-bridge/v1','advent_calendar_2026','data-campusfr-bridge']) {
 if(!js.includes(text))throw new Error('Missing Bridge contract: '+text);
}
console.log('Compiled Bridge verified (not an authorization to deploy)');
