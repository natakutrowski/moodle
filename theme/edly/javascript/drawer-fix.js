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
    const moveBlocks = () => {
        const xpBlock = document.querySelector('.block_xp');
        const questBlock = document.querySelector('[data-block="gearup"]');

        const side = document.querySelector('#block-region-side-pre');
        const top = document.querySelector('#block-region-above-content');

        if (!side || !top) {
            return;
        }

        if (window.innerWidth <= 768) {
            // Mobile:
            // 1. Level Up Quest tout en haut
            // 2. XP juste en dessous

            if (xpBlock && !top.contains(xpBlock)) {
                top.prepend(xpBlock);
            }

            if (questBlock) {
                // prepend après XP => Quest passe devant XP.
                top.prepend(questBlock);
            }
        } else {
            // Desktop:
            // Les deux blocs reviennent dans la colonne de droite.

            if (questBlock && !side.contains(questBlock)) {
                side.prepend(questBlock);
            }

            if (xpBlock && !side.contains(xpBlock)) {
                side.appendChild(xpBlock);
            }
        }
    };

    window.addEventListener('load', moveBlocks);
    window.addEventListener('resize', moveBlocks);
})();