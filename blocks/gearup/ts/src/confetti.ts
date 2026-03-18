import JSConfetti from 'js-confetti';

var instance: JSConfetti | undefined;

export const getJsConfetti = () => {
    if (!instance) {
        instance = new JSConfetti();
        /** @ts-ignore */
        instance.canvas.style.zIndex = 999999; // Monkey-patch the canvas z-index.
    }
    return instance;
};

export const throwCelebrationConfettis = () => {
    const confetti = getJsConfetti();
    const opts = {};
    confetti.addConfetti(opts);
    setTimeout(() => confetti.addConfetti(opts), 350);
};

const randomArgs = [
    {},
    { emojis: ['🌈', '⚡️', '✨', '💫', '🌸'] },
    { emojis: ['🎓', '👑'], confettiRadius: 100, confettiNumber: 30 },
    {
        confettiColors: ['#ffbe0b', '#fb5607', '#ff006e', '#8338ec', '#3a86ff'],
        confettiRadius: 10,
        confettiNumber: 150,
    },
];

export const throwRandomConfettis = () => {
    const confetti = getJsConfetti();
    const opts = randomArgs[Math.floor(randomArgs.length * Math.random())];
    confetti.addConfetti(opts);
};
