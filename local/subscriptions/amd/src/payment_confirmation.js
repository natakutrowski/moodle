// This file is part of Moodle - https://moodle.org/
//
// M8E.1 — active Alfa confirmation splash.

const SELECTOR = '[data-alfa-payment-confirmation], [data-payment-confirmation]';

const sleep = (milliseconds) => new Promise(resolve => window.setTimeout(resolve, milliseconds));

const postCheck = async(root) => {
    const body = new URLSearchParams({
        paymentid: root.dataset.paymentid,
        reference: root.dataset.reference,
        sesskey: root.dataset.sesskey,
    });

    const response = await fetch(root.dataset.endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
    });

    if (response.status === 403) {
        return {status: 'unsafe'};
    }

    if (!response.ok && response.status !== 503) {
        throw new Error(`Unexpected Alfa poll response ${response.status}`);
    }

    return response.json();
};

const setProgress = (root, attempt, maximum) => {
    const progress = root.querySelector('[data-alfa-progress]');
    if (!progress) {
        return;
    }
    const ratio = Math.min(1, Math.max(0, attempt / maximum));
    progress.style.setProperty('--alfa-progress', `${Math.round(ratio * 100)}%`);
};

const complete = async(root) => {
    root.classList.add('is-confirmed');

    const title = root.querySelector('[data-alfa-title]');
    const message = root.querySelector('[data-alfa-message]');
    if (title) {
        title.textContent = root.dataset.confirmedTitle;
    }
    if (message) {
        message.textContent = root.dataset.confirmedMessage;
    }

    await sleep(550);
    window.location.replace(root.dataset.successUrl);
};

const failSafe = (root) => {
    window.location.replace(root.dataset.failureUrl);
};

const releaseSplash = (root) => {
    if (root.classList.contains('is-released')) {
        return;
    }

    root.classList.add('is-released');
    root.setAttribute('aria-hidden', 'true');

    window.setTimeout(() => {
        root.classList.add('is-background');
    }, 500);
};

const run = async(root) => {
    const fastAttempts = Number.parseInt(root.dataset.fastAttempts, 10) || 12;
    const fastInterval = Number.parseInt(root.dataset.fastInterval, 10) || 1250;
    const backgroundInterval = Number.parseInt(root.dataset.backgroundInterval, 10) || 5000;

    let attempt = 0;
    let consecutiveErrors = 0;

    // First ~15 seconds: premium blocking splash, checking Alfa frequently.
    while (attempt < fastAttempts) {
        attempt++;
        setProgress(root, attempt, fastAttempts);

        try {
            const result = await postCheck(root);
            consecutiveErrors = 0;

            if (result.status === 'complete') {
                await complete(root);
                return;
            }

            if (result.status === 'unsafe') {
                failSafe(root);
                return;
            }
        } catch (error) {
            consecutiveErrors++;
            // A temporary network/API error is deliberately recoverable.
            if (consecutiveErrors >= 3) {
                break;
            }
        }

        if (attempt < fastAttempts) {
            await sleep(fastInterval);
        }
    }

    // Do not trap the customer behind a spinner indefinitely. Reveal the normal
    // pending order page, but keep checking in the background while it is open.
    releaseSplash(root);

    while (document.visibilityState !== 'hidden') {
        await sleep(backgroundInterval);

        try {
            const result = await postCheck(root);

            if (result.status === 'complete') {
                window.location.replace(root.dataset.successUrl);
                return;
            }

            if (result.status === 'unsafe') {
                failSafe(root);
                return;
            }
        } catch (error) {
            // M8D callback and M8C cron remain authoritative recovery paths.
        }
    }
};

export const init = () => {
    document.querySelectorAll(SELECTOR).forEach(root => {
        if (root.dataset.initialized === '1') {
            return;
        }
        root.dataset.initialized = '1';
        run(root);
    });
};
