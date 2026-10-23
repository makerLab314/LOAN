<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        var root = document.documentElement, pref = root.dataset.theme || 'system';
        try { if (pref === 'system' && !{{ auth()->check() ? 'true' : 'false' }}) { pref = localStorage.getItem('theme') || 'system'; } } catch (e) {}
        var dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        root.classList.toggle('dark', dark);
    })();
</script>
