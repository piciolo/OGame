{{--
    Lightweight JS handlers shared by every merchant sub-page (resource-market,
    scrap, auctioneer, import/export). Replaces the previous _trader-sidebar
    partial which was rendering a permanent 4-link sidebar — OGame ufficiale
    instead keeps those links as DOM placeholders with display:none on sub-pages
    (the visible section header is the #header_text + sprite + back button).

    Bound only once per page-load via a global guard; safe to re-include after
    AJAX swap (the script tag re-executes but re-binding is no-op).
--}}
<script type="text/javascript">
    (function () {
        if (window.__merchantHandlersBound) return;
        window.__merchantHandlersBound = true;

        // Back-to-overview button: OGame ufficiale uses href="javascript:void(0)"
        // with a JS-driven navigation, no extra attributes. Destination is derived
        // from the current path: any merchant-module sub-page returns to /merchant
        // (the hub); the /merchant hub itself returns to /overview.
        function backToOverviewTarget() {
            var p = window.location.pathname;
            if (p === '/merchant' || p === '/merchant/') return '/overview';
            return '/merchant';
        }
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a.js_backToOverview');
            if (!a) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
            e.preventDefault();
            window.location.href = backToOverviewTarget();
        });
    })();
</script>
