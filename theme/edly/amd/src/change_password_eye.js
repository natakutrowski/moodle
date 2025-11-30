/* eslint-env amd */
define(['core/togglesensitive'], function (ToggleSensitive) {
  return {
    init: function () {
      if (!/\/login\/change_password\.php$/.test(location.pathname)) return;

      ['password','newpassword1','newpassword2'].forEach(function (name) {
        var input = document.querySelector('input[name="'+name+'"]');
        if (!input) return;
        if (!input.id) input.id = 'id_' + name;
        ToggleSensitive.init(input.id, false);
      });
    }
  };
});
