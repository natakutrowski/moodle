/* eslint-disable no-undef */
define([], function() {
  function debounce(fn, ms){ let t; return function(){ clearTimeout(t); t = setTimeout(()=>fn.apply(this, arguments), ms); }; }

  function init() {
    var email = document.getElementById('email');
    var hint  = document.getElementById('ls_email_hint');
    if (!email || !hint) return;

    var check = debounce(function() {
      var v = (email.value || '').trim();
      if (!v) { hint.textContent = ''; return; }
      fetch(M.cfg.wwwroot + '/local/subscriptions/ajax/check_email.php?email=' + encodeURIComponent(v), {
        credentials: 'same-origin'
      })
        .then(r => r.json())
        .then(j => {
          if (j && j.exists) {
            hint.innerHTML =
              (document.documentElement.lang || '').startsWith('fr')
              ? 'Un compte existe déjà avec cet email. ' +
                '<a class="link-primary fw-semibold" href="'+ M.cfg.wwwroot +'/login/index.php?returnurl='+ encodeURIComponent(location.href) +'">Se connecter</a>.'
              : 'An account already exists with this email. ' +
                '<a class="link-primary fw-semibold" href="'+ M.cfg.wwwroot +'/login/index.php?returnurl='+ encodeURIComponent(location.href) +'">Sign in</a>.';
          } else { hint.textContent = ''; }
        })
        .catch(()=>{ /* no-op */ });
    }, 400);

    email.addEventListener('input', check);
    email.addEventListener('blur', check);
  }

  return { init: init };
});
