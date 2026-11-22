import React from 'react';
import {createRoot} from 'react-dom/client';
import Index from '@/routes/index';
import {AdventProvider,type BridgePayload} from './advent-context';
import '@/styles.css';

const id='advent_calendar_2026',api='campusfr-showroom-bridge/v1';
for(const host of document.querySelectorAll<HTMLElement>(`[data-campusfr-bridge="${id}"]`)) {
  try {
    if(host.shadowRoot) continue;
    const script=document.getElementById(host.getAttribute('data-bridge-payload')||'');
    if(!script) throw new Error('Missing safe data');
    const payload=JSON.parse(script.textContent||'null') as BridgePayload;
    // Builder's opaque-origin sandbox exposes no live checkout or showroom context.
    if(!payload.showroom && window.location.origin==='null' && payload.api===api && payload.design?.id===id){
      payload.showroom={key:'',language:'ru',currency:payload.commerce?.currency||'EUR',ispreview:true};
      payload.commerce={currency:payload.commerce?.currency||'EUR',offers:[],currencies:[]};
    }
    if(payload?.api!==api || payload.design?.id!==id || !payload.showroom ||
      !['fr','en','ru'].includes(payload.showroom.language)) throw new Error('Bridge contract');
    // Moodle's surrounding theme must never add a visible top rule to our hero.
    host.style.border='0';
    host.style.borderTop='0';
    host.style.outline='0';
    host.style.boxShadow='none';
    const shadow=host.attachShadow({mode:'open'});
    const link=document.createElement('link');link.rel='stylesheet';link.href=host.getAttribute('data-bridge-css')||'';
    shadow.appendChild(link);
    const node=document.createElement('div');node.setAttribute('data-advent-root','1');shadow.appendChild(node);
    shadow.addEventListener('click',event=>{
      const path=event.composedPath();
      const anchor=path.find(n=>n instanceof HTMLAnchorElement) as HTMLAnchorElement|undefined;
      if(!anchor)return;
      const href=anchor.getAttribute('href')||'';
      if(href.startsWith('#')) {event.preventDefault();const target=href.slice(1);
        if(/^[a-z][a-z0-9_-]{0,60}$/i.test(target)) shadow.getElementById(target)?.scrollIntoView({behavior:'smooth'});
        return;}
      // Only exact official Commerce links from the server are eligible for external navigation.
      if (![payload.links?.telegram,payload.links?.whatsapp,payload.links?.support,
            payload.links?.privacy,payload.links?.terms,payload.links?.offer].includes(href)) {event.preventDefault();return;}
      if(href.startsWith('mailto:')) return;
      try{const u=new URL(href);if(u.protocol!=='https:')event.preventDefault();}catch{event.preventDefault();}
    },true);
    createRoot(node).render(<AdventProvider initial={payload}><Index/></AdventProvider>);
    host.dataset.bridgeStatus='mounted';
    host.querySelector('.campusfr-bridge-prerender')?.remove();
  }catch(error){host.dataset.bridgeStatus='error';const p=document.createElement('p');p.textContent='CampusFR : présentation indisponible';host.appendChild(p);}
}
