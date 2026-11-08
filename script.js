(function () {
    const toggle = document.getElementById('menuToggle');
    const nav = document.getElementById('mainNav');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('active');
            toggle.setAttribute('aria-expanded', open);
        });
    }
})();