/** Moves the provisional-account guidance above the Moodle login form. */
export const init = () => {
    const run = () => {
        const notice = document.querySelector('[data-provisional-account-login-notice]');
        if (!(notice instanceof HTMLElement)) {
            return;
        }
        const target = document.querySelector('.login-container, .loginform, #region-main');
        if (target instanceof HTMLElement) {
            target.prepend(notice);
        }
        notice.hidden = false;
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, {once: true});
    } else {
        run();
    }
};
