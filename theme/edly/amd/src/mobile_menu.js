// theme/edly/amd/src/mobile_menu.js
import $ from 'jquery';

const S = {
  primary: '#menu-primary-menu',
  togglers: '.meanmenu-reveal, .navbar-toggler',
  mobileContainer: '#mobilePrimaryMenuContainer',
  mobileList: '#mobilePrimaryMenu',
  btn: '.campus-menu-toggle',
  offcanvasId: 'campusMobileMenu',
  userWraps: '.navbar-user-wrap, .edly-header-user-sec',
  userToggle: '[data-bs-toggle="dropdown"], .dropdown-toggle',
  topbarDetails: [
    '.campus-customer-nav__admin-menu',
    '.campus-customer-nav-mobile__admin-menu',
    '.campus-topbar-language',
    '.campus-topbar-user'
  ].join(', ')
};
const bs = () => window.bootstrap || null;

/* ---- Menu principal ---- */
function wirePrimaryMenu(){
  const $primary = $(S.primary);
  const hasMenu = $primary.length && $primary.find('li').length > 0;
  $(S.togglers).toggleClass('d-none', !hasMenu);
  $('body').toggleClass('has-primary-menu', hasMenu)
           .toggleClass('no-primary-menu', !hasMenu);
  if (hasMenu){
    $(S.mobileContainer).removeClass('d-none');
    $(S.mobileList).html($primary.html());
  }
}

/* ---- Offcanvas visiteurs / invités ---- */
function wireOffcanvas(){
  const B = bs(); if (!B) return;
  const el = document.getElementById(S.offcanvasId); if (!el) return;
  const oc = B.Offcanvas.getOrCreateInstance(el);
  document.querySelectorAll(S.btn).forEach(btn=>{
    btn.addEventListener('click', (e)=>{ e.preventDefault(); e.stopPropagation();
      document.body.classList.add('campus-offcanvas-open'); btn.hidden = true; oc.show();
    }, {passive:false});
  });
  el.addEventListener('hidden.bs.offcanvas', ()=>{
    document.body.classList.remove('campus-offcanvas-open');
    document.querySelectorAll(S.btn).forEach(b=> b.hidden = false);
  });
}

/* ---- Dropdown user (laisse Bootstrap gérer) ---- */
function wireUserDropdown(){
  const B = bs(); if (!B) return;
  document.querySelectorAll(S.userWraps).forEach(wrap=>{
    const toggle = wrap.querySelector(S.userToggle);
    if (toggle){ B.Dropdown.getOrCreateInstance(toggle, { autoClose: 'outside' }); }
  });
}

/* ---- Details topbar : fermeture au clic extérieur ---- */
let topbarDetailsWired = false;
function wireTopbarDetailsAutoClose(){
  if (topbarDetailsWired) return;
  topbarDetailsWired = true;

  document.addEventListener('click', (e) => {
    document.querySelectorAll(`${S.topbarDetails}[open]`).forEach(details => {
      if (!details.contains(e.target)) {
        details.removeAttribute('open');
      }
    });
  });
}

/* ---- Fallback “dur” : rendre un item du carrousel visible ---- */
function showUserMenuItem(menu, targetId){
  const carousel = menu.querySelector('#usermenu-carousel');
  if (!carousel) return;
  const items = carousel.querySelectorAll('.carousel-item');
  let switched = false;
  items.forEach(item=>{
    const active = item.id === targetId;
    item.classList.toggle('active', active);
    if (active) switched = true;
  });
  if (!switched && items.length){
    items.forEach(i=> i.classList.remove('active'));
    (carousel.querySelector('#carousel-item-main') || items[0]).classList.add('active');
  }
}

/* ---- Délégation globale : liens Langue ---- */
let wired = false;
function wireUserMenuCarouselDelegation(){
  if (wired) return; wired = true;
  document.addEventListener('click', (e)=>{
    const link = e.target.closest('.carousel-navigation-link');
    if (!link) return;

    const menu = link.closest('#user-action-menu') || document.getElementById('user-action-menu');
    if (!menu) return;

    const targetId = link.getAttribute('data-carousel-target-id') || 'carousel-item-main';
    e.preventDefault(); e.stopPropagation();

    // Essai Bootstrap 5 (si présent)
    const B = bs();
    const carEl = menu.querySelector('#usermenu-carousel');
    if (B && carEl){
      try{
        const car = B.Carousel.getOrCreateInstance(carEl, { interval:false, touch:false, keyboard:false, wrap:false });
        const items = Array.from(carEl.querySelectorAll('.carousel-item'));
        const idx = items.findIndex(it => it.id === targetId);
        if (idx >= 0){ car.to(idx); return; }
      }catch(_){
        null;
      }
    }
    // Fallback : on force l’item actif (pas d’anim, mais fiable)
    showUserMenuItem(menu, targetId);
  }, true);
}

function stripPreferences(menu){
  // Supprime le lien /user/preferences.php où qu’il soit (main ou sous-menu)
  const sel = 'a[href*="/user/preferences.php"]';
  menu.querySelectorAll(sel).forEach(a => {
    const item = a.closest('.dropdown-item') || a;
    const prev = item.previousElementSibling;
    const next = item.nextElementSibling;
    item.remove();

    // Nettoie des séparateurs orphelins
    [prev, next].forEach(el => {
      if (!el) return;
      if (el.classList && el.classList.contains('dropdown-divider')) {
        const before = el.previousElementSibling;
        const after  = el.nextElementSibling;
        if (!before || !after || (before.classList.contains('dropdown-divider') || after.classList.contains('dropdown-divider'))) {
          el.remove();
        }
      }
    });
  });
}

function wireStripPreferencesOnOpen(){
  // À chaque ouverture du dropdown utilisateur, on retire « Préférences »
  document.addEventListener('shown.bs.dropdown', (e) => {
    const toggle = e.target; // l’élément qui porte data-bs-toggle="dropdown"
    const wrap = toggle.closest('.navbar-user-wrap, .edly-header-user-sec');
    if (!wrap) return;
    const menu = wrap.querySelector('#user-action-menu');
    if (menu) stripPreferences(menu);
  });
  // Cas où le menu est déjà présent/visible au chargement (rare)
  const m = document.getElementById('user-action-menu');
  if (m) stripPreferences(m);
}


/* ---- Init ---- */
export const init = () => {
  const apply = () => {
    wirePrimaryMenu();
    wireOffcanvas();
    wireUserDropdown();
    wireTopbarDetailsAutoClose();
    wireUserMenuCarouselDelegation();
    wireStripPreferencesOnOpen();
  };
  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', apply, { once:true });
  } else {
    setTimeout(apply, 0);
  }
  window.addEventListener('resize', () => setTimeout(wireUserDropdown, 0));
};
