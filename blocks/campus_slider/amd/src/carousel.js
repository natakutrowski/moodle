/* eslint-env amd */
define([], function() {

    return {

        init: function() {

            const track = document.querySelector('.slider-track');
            const cards = document.querySelectorAll('.slider-card');
            const dots  = document.querySelectorAll('.dot');
            cards.forEach((card, index) => {

                card.addEventListener('mouseenter', () => {

                    cards.forEach(c => c.classList.remove('active'));

                    card.classList.add('active');

                    dots.forEach(d => d.classList.remove('active'));
                    if (dots[index]) dots[index].classList.add('active');

                });

            });

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

            /* clic sur dots */

            dots.forEach((dot, index) => {

                dot.addEventListener('click', () => {

                    track.scrollTo({

                        left: cards[index].offsetLeft,
                        behavior: 'smooth'

                    });

                });

            });

            /* centre card 2 au chargement */

            if (cards.length > 1) {

                track.scrollTo({

                    left: cards[1].offsetLeft,
                    behavior: 'instant'

                });

            }

            update();

        }

    };

});