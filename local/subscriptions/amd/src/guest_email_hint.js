// Commerce 7.96H12.9-A5.7.1.1 — legacy email enumeration hint retired.

export const init = () => {
    const hint =
        document.getElementById(
            'ls_email_hint'
        );

    if (!(hint instanceof HTMLElement)) {
        return;
    }

    hint.textContent = '';
    hint.hidden = true;
};
