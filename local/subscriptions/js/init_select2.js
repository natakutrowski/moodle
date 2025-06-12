document.addEventListener('DOMContentLoaded', function () {
  const selects = document.querySelectorAll('select');
  selects.forEach(select => {
    if (typeof jQuery !== 'undefined' && jQuery().select2) {
      jQuery(select).select2({ width: '100%' });
    }
  });
});
