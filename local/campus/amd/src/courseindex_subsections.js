import $ from 'jquery';

/**
 * Ajoute le rond (vert ou vide) dans le titre de la sous-section.
 *
 * @param {JQuery} $section
 * @param {boolean} isComplete
 */
const addDot = ($section, isComplete) => {
    const $title = $section.find('.courseindex-section-title').first();
    if (!$title.length) {
        return;
    }

    // Supprimer une éventuelle pastille précédente.
    $title.find('.campus-subsection-dot').remove();

    const dotClass = isComplete
        ? 'campus-subsection-dot campus-subsection-dot-complete'
        : 'campus-subsection-dot campus-subsection-dot-incomplete';

    const $dot = $('<span>', {
        class: dotClass,
        'aria-hidden': 'true'
    });

    const $chevron = $title.find('.courseindex-chevron').first();
    if ($chevron.length) {
        $chevron.after($dot);
    } else {
        $title.prepend($dot);
    }
};

/**
 * Calcule pour chaque sous-section (Блок ...) si elle est complétée,
 * et applique la pastille correspondante.
 *
 * On se base uniquement sur les classes :
 *  - completion_none       → ignorée
 *  - completion_complete   → comptée comme complète
 *  - autre (incomplete…)   → comptée comme incomplète
 */
const applyDots = () => {
    const $sections = $('#courseindex .courseindex-section.delegated-section');
    if (!$sections.length) {
        return;
    }

    $sections.each((index, section) => {
        const $section = $(section);
        const $completions = $section.find('li.courseindex-item[data-for="cm"] .completioninfo');

        if (!$completions.length) {
            addDot($section, false);
            return;
        }

        let hasCompletable = false;
        let hasIncomplete = false;

        $completions.each((i, el) => {
            const $c = $(el);
            const classes = $c.attr('class') || '';

            // Pas de suivi de complétion → on ignore.
            if (classes.indexOf('completion_none') !== -1) {
                return;
            }

            hasCompletable = true;

            // Si le span n’a pas completion_complete → on considère qu’il reste quelque chose à faire.
            if (classes.indexOf('completion_complete') === -1) {
                hasIncomplete = true;
            }
        });

        const isComplete = hasCompletable && !hasIncomplete;
        addDot($section, isComplete);
    });
};

export const init = () => {
    const MAX_TRIES = 20;
    const INTERVAL = 250;
    let tries = 0;

    const tick = () => {
        tries++;
        applyDots();

        if (tries < MAX_TRIES) {
            setTimeout(tick, INTERVAL);
        }
    };

    // On laisse un petit délai pour que le sommaire et la complétion soient calculés.
    setTimeout(tick, INTERVAL);
};
