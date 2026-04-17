document.addEventListener('click', function (e) {
    if (e.target.closest('.section, .course-section')) {
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 300);
    }
});