document.addEventListener('DOMContentLoaded', function () {

    const hero = document.querySelector('.bloc1');
    const topbar = document.querySelector('.campus-topbar');

    if (!hero || !topbar) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        topbar.classList.add('is-solid');
        return;
    }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                topbar.classList.remove('is-solid');
            } else {
                topbar.classList.add('is-solid');
            }
        });
    }, {
        threshold: 0.15
    });

    observer.observe(hero);
});

document.addEventListener('DOMContentLoaded', function () {

    const dropdowns = document.querySelectorAll('.campus-lang-dropdown');

    dropdowns.forEach(dropdown => {

        const trigger = dropdown.querySelector('.campus-lang-trigger');

        if (!trigger) return;

        // Toggle au clic
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();

            // Fermer les autres dropdowns
            document.querySelectorAll('.campus-lang-dropdown.open')
                .forEach(el => {
                    if (el !== dropdown) {
                        el.classList.remove('open');
                    }
                });

            dropdown.classList.toggle('open');
        });

    });

    // Click ailleurs → ferme tout
    document.addEventListener('click', function () {
        document.querySelectorAll('.campus-lang-dropdown.open')
            .forEach(el => el.classList.remove('open'));
    });

});