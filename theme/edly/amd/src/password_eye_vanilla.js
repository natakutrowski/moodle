/* eslint-env amd */
define([], function () {

  function addStylesOnce() {
    if (document.getElementById('edly-password-eye-css')) return;
    var css = ''
      + '.password-toggle-wrapper{position:relative;}'
      + '.password-toggle-wrapper .toggle-eye{position:absolute;top:50%;right:.75rem;transform:translateY(-50%);'
      + 'border:0;background:transparent;padding:.25rem;line-height:1;cursor:pointer;}'
      + '.password-toggle-wrapper input.form-control{padding-right:2.5rem;}';
    var s = document.createElement('style');
    s.id = 'edly-password-eye-css';
    s.type = 'text/css';
    s.appendChild(document.createTextNode(css));
    document.head.appendChild(s);
  }

  function makeEyeFor(input) {
    if (!input || input.dataset.eyeInit) return;
    input.dataset.eyeInit = '1';

    // Crée un wrapper et garde l'order input -> invalid-feedback (pour Bootstrap)
    var parent = input.parentNode;
    var wrapper = document.createElement('div');
    wrapper.className = 'password-toggle-wrapper w-100';
    parent.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    // Si l’élément d’erreur est juste après, on le déplace dans le wrapper
    var feedback = input.nextElementSibling;
    if (!feedback || !feedback.classList || !feedback.classList.contains('invalid-feedback')) {
      var alt = document.getElementById('id_error_' + (input.getAttribute('name') || '').toLowerCase());
      if (alt && alt.classList.contains('invalid-feedback')) feedback = alt;
    }
    if (feedback && feedback.parentNode === parent) {
      wrapper.appendChild(feedback);
    }

    // Bouton œil
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'toggle-eye';
    btn.setAttribute('aria-label', 'Показать/Скрыть пароль');
    btn.setAttribute('aria-pressed', 'false');
    btn.innerHTML = '<i class="icon fa fa-eye fa-fw" aria-hidden="true"></i>';

    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.innerHTML = show
        ? '<i class="icon fa fa-eye-slash fa-fw" aria-hidden="true"></i>'
        : '<i class="icon fa fa-eye fa-fw" aria-hidden="true"></i>';
    });

    wrapper.appendChild(btn);
  }

  function initEyes() {
    ['id_password','id_newpassword1','id_newpassword2'].forEach(function (id) {
      makeEyeFor(document.getElementById(id));
    });
  }

  return {
    init: function () {
      if (!/\/login\/change_password\.php$/.test(location.pathname)) return;
      addStylesOnce();
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEyes, { once: true });
      } else {
        initEyes();
      }
    }
  };
});
