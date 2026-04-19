(function () {
    const fixDrawerHeight = () => {
        const drawer = document.querySelector('#theme_boost-drawers-courseindex');
        if (!drawer) return;

        const rect = drawer.getBoundingClientRect();
        const availableHeight = window.innerHeight - rect.top;

        drawer.style.height = availableHeight + 'px';
    };

    // au chargement
    window.addEventListener('load', () => {
        setTimeout(fixDrawerHeight, 300);
    });

    // au resize
    window.addEventListener('resize', fixDrawerHeight);

    // au clic (important pour Moodle quand on ouvre/ferme sections)
    document.addEventListener('click', () => {
        setTimeout(fixDrawerHeight, 300);
    });
})();

(function () {
    const moveBlock = () => {
        const block = document.querySelector('.block_xp');
        const side = document.querySelector('#block-region-side-pre');
        const top = document.querySelector('#block-region-above-content');

        if (!block || !side || !top) return;

        if (window.innerWidth <= 768) {
            // mobile → en haut
            if (!top.contains(block)) {
                top.prepend(block);
            }
        } else {
            // desktop → à droite
            if (!side.contains(block)) {
                side.prepend(block);
            }
        }
    };

    window.addEventListener('load', moveBlock);
    window.addEventListener('resize', moveBlock);
})();