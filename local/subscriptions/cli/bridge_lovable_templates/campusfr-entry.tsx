/** CampusFR X2.2 draft Bridge. Not a Commerce implementation. REVIEW BEFORE DEPLOY. */
import React from 'react';
import {createRoot} from 'react-dom/client';
import Page from '__CAMPUSFR_PAGE_IMPORT__';
import {BridgeTextProvider} from './campusfr-i18n';
import '../styles.css';

const designId = '__CAMPUSFR_DESIGN_ID__';
const apiVersion = 'campusfr-showroom-bridge/v1';

type Payload = {
  api: string;
  design: {id: string};
  content?: Record<string, Record<string, string>>;
  showroom?: {ispreview?: boolean};
};

function error(host: HTMLElement, reason: string) {
  host.dataset.bridgeStatus = 'error';
  const message = document.createElement('p');
  message.textContent = `CampusFR Bridge : ${reason}`;
  message.style.cssText = 'color:#ac1838;padding:1rem;font:600 14px/1.5 system-ui';
  host.append(message);
}

for (const host of document.querySelectorAll<HTMLElement>(`[data-campusfr-bridge="${designId}"]`)) {
  try {
    if (host.shadowRoot) continue;
    const element = document.getElementById(host.getAttribute('data-bridge-payload') || '');
    if (!element) { error(host, 'données CMS absentes'); continue; }
    const payload = JSON.parse(element.textContent || 'null') as Payload;
    if (payload?.api !== apiVersion || payload?.design?.id !== designId) {
      error(host, 'contrat incompatible'); continue;
    }
    const shadow = host.attachShadow({mode: 'open'});
    const stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.href = host.getAttribute('data-bridge-css') || '';
    shadow.append(stylesheet);
    const mount = document.createElement('div');
    shadow.append(mount);
    // Safe draft: block form submissions and outside navigation in the Bridge.
    // This does NOT replace the required Commerce review for an approved build.
    shadow.addEventListener('submit', event => event.preventDefault(), true);
    shadow.addEventListener('click', event => {
      const source = event.target;
      const anchor = source instanceof Element ? source.closest('a[href]') : null;
      if (anchor && !anchor.getAttribute('href')?.startsWith('#')) {
        event.preventDefault();
      }
    }, true);
    createRoot(mount).render(
      <BridgeTextProvider content={payload.content || {}}><Page /></BridgeTextProvider>
    );
    host.dataset.bridgeStatus = 'mounted';
    host.querySelector('.campusfr-bridge-prerender')?.remove();
  } catch (exception) {
    error(host, String(exception));
  }
}
