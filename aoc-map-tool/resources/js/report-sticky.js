(function () {
    let initialized = false;

    function setReportStickyOffset() {

        if (document.body.classList.contains('modal-open')) return;

        const report = document.querySelector('.report-sticky-compact');
        if (!report) return;

        const stickyTop = report.querySelector('.top-sticky-container');
        if (!stickyTop) return;

        const stickyBottom =
            stickyTop.getBoundingClientRect().bottom + window.scrollY;

        const reportTop =
            report.getBoundingClientRect().top + window.scrollY;

        const offset = Math.max(0, stickyBottom - reportTop);

        report.style.setProperty('--report-sticky-top', offset + 'px');
    }

    document.addEventListener('DOMContentLoaded', () => {
        setReportStickyOffset();
    });

    window.addEventListener('scroll', () => {
        if (initialized) return;
        initialized = true;
        setReportStickyOffset();
    }, {once: true});

    window.addEventListener('load', setReportStickyOffset);
    window.addEventListener('resize', setReportStickyOffset);

    if (window.jQuery) {
        $(document).on('tabsactivate', setReportStickyOffset);
        $(document).on(
            'change',
            '.filters-common input, .filters-common select',
            setReportStickyOffset
        );
        $(document).on('draw.dt', setReportStickyOffset);
    }
})();
