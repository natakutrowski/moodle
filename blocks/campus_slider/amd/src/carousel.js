/* eslint-env amd */

define([], function() {

    return {

        init: function() {

            const sliders = document.querySelectorAll('.campus-slider');

            if (!sliders.length) return;

            sliders.forEach(slider => {

                const track = slider.querySelector('.slider-track');
                const cards = slider.querySelectorAll('.slider-card');
                const dots  = slider.querySelectorAll('.dot');

                if (!track || cards.length === 0) return;

                function centerCard(index = 1) {

                    const card = cards[index];
                    if (!card) return;

                    const offset =
                        card.offsetLeft
                        - (track.offsetWidth / 2)
                        + (card.offsetWidth / 2);

                    track.scrollTo({
                        left: offset,
                        behavior: 'auto'
                    });

                }

                function update() {

                    const center = track.scrollLeft + track.offsetWidth / 2;

                    cards.forEach((card, i) => {

                        const cardCenter =
                            card.offsetLeft + card.offsetWidth / 2;

                        if (Math.abs(center - cardCenter) < card.offsetWidth / 2) {

                            card.classList.add('active');

                            dots.forEach(d => d.classList.remove('active'));
                            if (dots[i]) dots[i].classList.add('active');

                        } else {

                            card.classList.remove('active');

                        }

                    });

                }

                track.addEventListener('scroll', update);

                dots.forEach((dot, index) => {

                    dot.addEventListener('click', () => {
                        centerCard(index);
                    });

                });

                /* 🔥 FIX PRINCIPAL */

                function initSlider() {

                    centerCard(1);
                    update();

                }

                if (document.readyState === 'complete') {
                    setTimeout(initSlider, 50);
                } else {
                    window.addEventListener('load', () => {
                        setTimeout(initSlider, 50);
                    });
                }

                /* resize */

                window.addEventListener('resize', () => {
                    setTimeout(() => centerCard(1), 50);
                });

            });

        }

    };

});