/* CampusFR P2J: administrative DEV-only checkout adapter for a trusted ShadowRoot.
 * Installed only by the exact #8 commerce_test=1 preview, not by ordinary
 * previews or published showrooms. cart_action.php remains authoritative.
 */
(() => {
  'use strict';
  const TEST_PATH = '/local/subscriptions/admin/commerce/showrooms/preview.php';
  const CART_PATH = '/local/subscriptions/cart_action.php';
  const DESIGN_ID = 'articles_alf_reimport';
  const SHOWROOM_KEY = 'articlesalfbridgepilot';
  const FIELD_NAMES = new Set([
    'action', 'currency', 'sku', 'priceid', 'quantity', 'sesskey', 'source',
    'showroom', 'showroomoffer', 'donationsku', 'donationpriceid',
    'donationshowroomoffer', 'donationunits'
  ]);
  const adminRoute = location.pathname === TEST_PATH &&
    new URLSearchParams(location.search).get('id') === '8' &&
    new URLSearchParams(location.search).get('commerce_test') === '1';
  const publicPilotPaths = new Set([
    '/articles-francais',
    '/french-articles',
    '/francuzskie-artikli'
  ]);
  const publicPilotRoute = location.hostname === 'moodle-v2.campusfr.fr' &&
    publicPilotPaths.has(location.pathname);
  if (location.protocol !== 'https:' || (!adminRoute && !publicPilotRoute)) return;

  function initialize() {
  const host = document.querySelector('[data-campusfr-bridge="' + DESIGN_ID + '"]');
  const script = document.getElementById('campusfr-bridge-data');
  let payload;
  try { payload = JSON.parse(script?.textContent || 'null'); } catch { return; }
  if (!host || payload?.api !== 'campusfr-showroom-bridge/v1' ||
      payload.design?.id !== DESIGN_ID || payload.design?.version !== '0.4.10' ||
      payload.showroom?.key !== SHOWROOM_KEY || payload.showroom?.ispreview !== false ||
      !((adminRoute && payload.showroom?.ischeckouttest === true) ||
        (publicPilotRoute && payload.showroom?.isdevpublishedpilot === true &&
         payload.showroom?.ischeckouttest === false)) ||
      !Array.isArray(payload.commerce?.offers)) return;

  let submitting = false;
  // A native POST can put this document into the browser's back/forward cache.
  // Restoring it does NOT rerun initialize(); without lifecycle reset the
  // submitting guard remains true and all future clicks are silently ignored.
  function resetAfterNavigation() {
    submitting = false;
    // Remove a stale light-DOM POST proxy left in the cached document.
    document.querySelectorAll('form[data-campusfr-dev-checkout-proxy="1"]')
      .forEach(proxy => proxy.remove());
  }
  window.addEventListener('pagehide', resetAfterNavigation);
  window.addEventListener('pageshow', event => {
    if (event.persisted) resetAfterNavigation();
  });
  function problem(button) {
    let message = button.parentElement?.querySelector('[data-campusfr-checkout-error]');
    if (!message) {
      message = document.createElement('p');
      message.dataset.campusfrCheckoutError = '1';
      message.setAttribute('role', 'alert');
      message.style.cssText = 'color:#ffb9ca;font:600 13px/1.5 system-ui;margin-top:.5rem;text-align:center';
      button.parentElement?.append(message);
    }
    if (message) message.textContent = 'Impossible de préparer le panier. Recharge la page de test.';
  }
  function digits(value) {
    return typeof value === 'string' && /^[1-9][0-9]{0,9}$/.test(value);
  }
  function fieldsOf(form) {
    const fields = new Map();
    const controls = Array.from(form.querySelectorAll('input'));
    if (controls.length < 8 || controls.length > 14) return null;
    for (const input of controls) {
      if (input.type !== 'hidden' || !FIELD_NAMES.has(input.name) ||
          fields.has(input.name) || input.value.length > 160) return null;
      fields.set(input.name, input.value);
    }
    return fields;
  }
  function validate(form) {
    if (!(form instanceof HTMLFormElement) || form.dataset.campusfrNativeCheckoutV46 !== '1' ||
        form.method.toLowerCase() !== 'post') return null;
    let action;
    // input[name=action] shadows form.action: ALWAYS read the HTML attribute.
    try { action = new URL(form.getAttribute('action') || '', location.href); } catch { return null; }
    if (action.protocol !== 'https:' || action.origin !== location.origin ||
        action.pathname !== CART_PATH || action.search !== '' || action.hash !== '') return null;
    const values = fieldsOf(form);
    if (!values || values.get('action') !== 'buynow' || values.get('source') !== 'showroom' ||
        values.get('showroom') !== SHOWROOM_KEY || values.get('quantity') !== '1' ||
        !digits(values.get('priceid')) ||
        values.get('sesskey') !== payload.commerce.offers[0]?.sesskey ||
        !/^[A-Z]{3}$/.test(values.get('currency') || '')) return null;
    const currency = values.get('currency');
    const currentUrlCurrency = new URLSearchParams(location.search).get('currency');
    const initialCurrency = payload.commerce.currency;
    if (currency !== (currentUrlCurrency || initialCurrency)) return null;
    if (!payload.commerce.currencies?.some(x => x.value === currency)) return null;
    const role = values.get('showroomoffer');
    if (!['course', 'bundle', 'donation'].includes(role)) return null;
    const offers = payload.commerce.offers;
    const course = offers.find(x => x.role === 'course' && !x.isdonation);
    const donation = offers.find(x => x.role === 'donation' && x.isdonation);
    const chosen = role === 'donation' ? donation : course;
    if (!chosen || chosen.available !== true || chosen.owned === true ||
        !/^[A-Z0-9_.-]{4,100}$/.test(values.get('sku') || '') ||
        chosen.sku !== values.get('sku') || !digits(values.get('priceid')) ||
        (currency === initialCurrency && String(chosen.priceid) !== values.get('priceid'))) return null;
    if (role === 'course') {
      if (values.has('donationunits') || values.has('donationsku') ||
          values.has('donationpriceid') || values.has('donationshowroomoffer')) return null;
    } else {
      const units = values.get('donationunits');
      if (!donation || !digits(units) || Number(units) > Number(donation.donationmaxunits) ||
          donation.sku !== (role === 'donation' ? values.get('sku') : values.get('donationsku'))) return null;
      if (role === 'bundle') {
        if (!course?.hasdonationcompanion ||
            values.get('donationshowroomoffer') !== 'donation' ||
            !digits(values.get('donationpriceid')) ||
            (currency === initialCurrency &&
              String(donation.priceid) !== values.get('donationpriceid'))) return null;
      } else if (values.has('donationsku') || values.has('donationpriceid') ||
                 values.has('donationshowroomoffer')) return null;
    }
    return {action: action.href, values};
  }
  // Click is a composed event: the real button/form are available in composedPath()
  // even though the event target is retargeted when it crosses a ShadowRoot.
  document.addEventListener('click', event => {
    const path = event.composedPath?.() || [];
    if (!path.includes(host)) return;
    const button = path.find(n => n instanceof HTMLButtonElement && n.type === 'submit');
    if (!button || !event.isTrusted) return;
    const form = button.form;
    if (!form || form.dataset.campusfrNativeCheckoutV46 !== '1') return;
    // Always stop the shadow-local form's native activation; after validation,
    // submit a new light-DOM form, which the normal browser and Moodle can handle.
    event.preventDefault();
    if (submitting) return;
    const validated = validate(form);
    if (!validated) { problem(button); return; }
    submitting = true;
    const outer = document.createElement('form');
    outer.method = 'post';
    outer.action = validated.action;
    outer.style.display = 'none';
    outer.dataset.campusfrDevCheckoutProxy = '1';
    for (const [name, value] of validated.values) {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = name; input.value = value;
      outer.appendChild(input);
    }
    document.body.appendChild(outer);
    // Native method avoids a second submit handler and keeps a standard POST.
    HTMLFormElement.prototype.submit.call(outer);
  }, true);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, {once: true});
  } else {
    initialize();
  }
})();
