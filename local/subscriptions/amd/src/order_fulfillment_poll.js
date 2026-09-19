// Commerce 7.96H12.9.2 — post-payment fulfillment reconciliation UX.

const SELECTOR =
    '[data-order-fulfillment-watch]';

const sleep = milliseconds =>
    new Promise(
        resolve => window.setTimeout(
            resolve,
            milliseconds
        )
    );

const check = async root => {
    const body =
        new URLSearchParams({
            reference:
                root.dataset.reference
                || '',
            sesskey:
                root.dataset.sesskey
                || '',
        });

    const response =
        await fetch(
            root.dataset.endpoint,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With':
                        'XMLHttpRequest',
                },
                body:
                    body.toString(),
            }
        );

    if (response.status === 403) {
        return {
            status: 'unsafe',
        };
    }

    if (!response.ok) {
        throw new Error(
            'Unexpected fulfillment status response.'
        );
    }

    return response.json();
};

const reloadReadyPage = () => {
    window.location.reload();
};

const run = async root => {
    const fastAttempts =
        Number.parseInt(
            root.dataset.fastAttempts,
            10
        )
        || 8;
    const fastInterval =
        Number.parseInt(
            root.dataset.fastInterval,
            10
        )
        || 2000;
    const backgroundInterval =
        Number.parseInt(
            root.dataset.backgroundInterval,
            10
        )
        || 5000;
    const timeoutMs =
        Number.parseInt(
            root.dataset.timeoutMs,
            10
        )
        || 90000;

    const startedAt =
        Date.now();
    let attempt =
        0;

    while (
        Date.now() - startedAt
        < timeoutMs
    ) {
        try {
            const result =
                await check(
                    root
                );

            if (result.status === 'ready') {
                root.classList.add(
                    'is-ready'
                );
                reloadReadyPage();
                return;
            }

            if (
                result.status === 'unsafe'
                || result.status === 'failed'
            ) {
                return;
            }
        } catch (error) {
            // Fulfillment remains durable server-side; a temporary polling
            // failure must never turn a successful payment into an error.
        }

        attempt += 1;

        await sleep(
            attempt < fastAttempts
                ? fastInterval
                : backgroundInterval
        );

        if (
            document.visibilityState
            === 'hidden'
        ) {
            await sleep(
                backgroundInterval
            );
        }
    }

    root.classList.add(
        'is-timeout'
    );
};

export const init = () => {
    document.querySelectorAll(
        SELECTOR
    ).forEach(root => {
        if (
            root.dataset.initialized
            === '1'
        ) {
            return;
        }

        root.dataset.initialized =
            '1';

        const refresh =
            root.querySelector(
                '[data-order-fulfillment-refresh]'
            );

        refresh?.addEventListener(
            'click',
            () => {
                window.location.reload();
            }
        );

        run(
            root
        );
    });
};
