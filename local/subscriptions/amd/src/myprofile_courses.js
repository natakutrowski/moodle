/* eslint-disable no-undef */
define([], function() {
  function rewriteLinks() {
    var base = (window.M && M.cfg && M.cfg.wwwroot) ? M.cfg.wwwroot : '';
    var anchors = document.querySelectorAll('a[href*="/user/view.php"][href*="course="]');
    anchors.forEach(function(a){
      try {
        var u = new URL(a.href, window.location.origin);
        var cid = u.searchParams.get('course');
        if (cid) a.href = base + '/course/view.php?id=' + cid;
      } catch(e) {/* noop */}
    });
  }

  // Renomme le <dt> qui annonce la liste des cours (node "Profils de cours")
  function renameCourseProfiles(label) {
    var dts = document.querySelectorAll('dl > dt');
    dts.forEach(function(dt){
      var dd = dt.nextElementSibling;
      if (!dd) return;

      // On détecte s'il s'agit bien du bloc des cours par la présence de liens cours
      var hasCourseLinks = dd.querySelector('a[href*="/course/view.php"], a[href*="/user/view.php"][href*="course="]');
      if (!hasCourseLinks) return;

      // Renomme le <dt>
      dt.textContent = label || 'Mes cours';
    });
  }

  function run(label) {
    try {
      rewriteLinks();
      renameCourseProfiles(label);
      // Repasser après lazy render (certains thèmes)
      setTimeout(function(){
        rewriteLinks();
        renameCourseProfiles(label);
      }, 400);
    } catch(e) { /* noop */ }
  }

  return {
    init: function(args) {
      var label = (args && args.label) || '';
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){ run(label); });
      } else {
        run(label);
      }
    }
  };
});
