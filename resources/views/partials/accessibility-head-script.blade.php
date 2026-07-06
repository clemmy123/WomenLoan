<script>
(function () {
    var root = document.documentElement;
    if (localStorage.getItem('theme') === 'dark') {
        root.classList.add('dark');
    }
    var fontSize = localStorage.getItem('a11y-font-size');
    if (fontSize === 'large') {
        root.classList.add('a11y-text-large');
    } else if (fontSize === 'xl') {
        root.classList.add('a11y-text-xl');
    }
    if (localStorage.getItem('a11y-high-contrast') === 'true') {
        root.classList.add('a11y-high-contrast');
    }
    if (localStorage.getItem('a11y-reduce-motion') === 'true') {
        root.classList.add('a11y-reduce-motion');
    }
})();
</script>
