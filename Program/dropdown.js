document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.user-menu').forEach(function (menu) {
        const button = menu.querySelector('.user-name');
        let closeTimer = null;
        let pinned = false;

        function setExpanded(expanded) {
            if (button) button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        function openMenu() {
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
            menu.classList.add('is-open');
            setExpanded(true);
        }

        function closeMenu() {
            menu.classList.remove('is-open');
            setExpanded(false);
        }

        function scheduleClose() {
            if (pinned) return;
            if (closeTimer) clearTimeout(closeTimer);
            closeTimer = setTimeout(closeMenu, 700);
        }

        menu.addEventListener('mouseenter', openMenu);
        menu.addEventListener('mouseleave', scheduleClose);

        if (button) {
            button.setAttribute('aria-expanded', 'false');
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                pinned = !pinned;
                if (pinned) openMenu();
                else closeMenu();
            });
        }

        menu.addEventListener('focusin', openMenu);
        menu.addEventListener('focusout', function (event) {
            if (!menu.contains(event.relatedTarget)) scheduleClose();
        });

        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target)) {
                pinned = false;
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                pinned = false;
                closeMenu();
                if (button) button.focus();
            }
        });
    });
});
