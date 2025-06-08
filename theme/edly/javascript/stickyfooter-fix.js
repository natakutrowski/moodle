document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;

    const footer = document.getElementById('sticky-footer');
    if (!footer) return;

    const updateFooterVisibility = () => {
        const isCoursePage = body.classList.contains('path-course');
        const isEditing = body.classList.contains('editing');
        const show = body.classList.contains('bulkenabled') && body.classList.contains('hasstickyfooter');

        if (isCoursePage && isEditing) {
            footer.style.display = show ? 'block' : 'none';
        } else {
            // Masquer si ce n’est pas une page concernée
            footer.style.display = 'none';
        }
    };

    // Exécuter immédiatement (au cas où les classes sont déjà là)
    updateFooterVisibility();

    // Observer les changements de classe dynamiques
    const observer = new MutationObserver(updateFooterVisibility);
    observer.observe(body, { attributes: true, attributeFilter: ['class'] });
});
