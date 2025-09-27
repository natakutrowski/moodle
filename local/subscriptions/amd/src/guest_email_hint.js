/* eslint-disable no-undef */
define(['core/str'], function(str) {
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
            str.get_string('existing_account_hint_html', 'local_subscriptions', {url: M.cfg.wwwroot +'/login/index.php?returnurl='+ encodeURIComponent(location.href)})
              .done(function(s) { hint.innerHTML = s; })    // on contrôle la string (HTML autorisé)
              .fail(function()  { hint.textContent = ''; });
          } else { hint.textContent = ''; }
        })
        .catch(()=>{ /* no-op */ });
    }, 400);

    email.addEventListener('input', check);
    email.addEventListener('blur', check);
  }

  return { init: init };
});
