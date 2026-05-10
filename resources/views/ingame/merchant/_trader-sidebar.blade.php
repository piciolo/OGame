{{--
    Trader sidebar — replicates OGame's persistent 4-link sidebar (#js_traderOverview).
    Included at the top of every sub-merchant body (resource-market, scrap, auctioneer,
    import/export) so the sidebar stays visible after AJAX swaps.

    Required input variable:
        $currentTrader  string  one of: 'resources', 'auctioneer', 'scrap', 'importexport'
--}}
@php
    $currentTrader = $currentTrader ?? null;
@endphp

<a href="{{ route('merchant.resource-market') }}"
   data-ajax-url="{{ route('merchant.resource-market.partial') }}"
   id="js_traderResources"
   class="js_trader trader_link tooltipLeft js_hideTipOnMobile ipiHintable ajax_nav {{ $currentTrader === 'resources' ? 'active' : '' }}"
   data-ipi-hint="ipiTraderResources"
   data-tooltip-title="{{ __('t_merchant.exchange_resources_desc') }}">
    <h2>{{ __('t_merchant.resource_market') }}</h2>
</a>
<a href="{{ route('auctioneer.index') }}"
   data-ajax-url="{{ route('auctioneer.partial') }}"
   id="js_traderAuctioneer"
   class="js_trader trader_link tooltipRight js_hideTipOnMobile ipiHintable ajax_nav {{ $currentTrader === 'auctioneer' ? 'active' : '' }}"
   data-ipi-hint="ipiTraderAuctioneer"
   data-tooltip-title="{{ __('t_merchant.auctioneer_desc') }}">
    <h2>{{ __('t_merchant.auctioneer') }}</h2>
</a>
<br>
<a href="{{ route('merchant.scrap') }}"
   data-ajax-url="{{ route('merchant.scrap.partial') }}"
   id="js_traderScrap"
   class="js_trader trader_link tooltipLeft js_hideTipOnMobile ipiHintable ajax_nav {{ $currentTrader === 'scrap' ? 'active' : '' }}"
   data-ipi-hint="ipiTraderScrap"
   data-tooltip-title="{{ __('t_merchant.scrap_merchant_desc') }}">
    <h2>{{ __('t_merchant.scrap_merchant') }}</h2>
</a>
<a href="{{ route('importexport.index') }}"
   data-ajax-url="{{ route('importexport.partial') }}"
   id="js_traderImportExport"
   class="js_trader trader_link tooltipRight js_hideTipOnMobile ipiHintable ajax_nav {{ $currentTrader === 'importexport' ? 'active' : '' }}"
   data-ipi-hint="ipiTraderImportExport"
   data-tooltip-title="{{ __('t_merchant.import_export_desc') }}">
    <h2>{{ __('t_merchant.import_export') }}</h2>
</a>

<style>
    /* OGame ufficiale renders the section title via the sprite background image
       on #header_text; the project-side <h2> exists for accessibility but its
       text would visually overlap the sprite text otherwise. Hide it visually
       on every trader page (hub + 4 sub-merchants), keeping it readable to
       screen readers (no display:none / visibility:hidden). */
    #traderOverview #planet #header_text > h2 {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
</style>

<script type="text/javascript">
    // SPA-style AJAX swap for the 4 trader sidebar links. Global guard prevents
    // double-binding when this partial is re-included after an AJAX content swap
    // (the sidebar markup is re-rendered, but we only want one click listener).
    (function () {
        if (window.__traderSidebarBound) return;
        window.__traderSidebarBound = true;

        function getWrapper() { return document.getElementById('contentWrapper'); }

        async function ajaxSwap(url, pushUrl) {
            const wrapper = getWrapper();
            if (!wrapper) { window.location.href = pushUrl; return; }

            wrapper.style.transition = 'opacity 0.2s ease';
            wrapper.style.opacity = '0.4';

            try {
                const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, cache: 'no-store' });
                if (!r.ok) { window.location.href = pushUrl; return; }
                const html = await r.text();

                wrapper.innerHTML = html;

                // Re-execute inline scripts of the swapped content (innerHTML doesn't run them)
                wrapper.querySelectorAll('script').forEach(old => {
                    const s = document.createElement('script');
                    for (const a of old.attributes) s.setAttribute(a.name, a.value);
                    s.textContent = old.textContent;
                    old.replaceWith(s);
                });

                // Re-init tooltips on the new content if the helper exists.
                if (typeof initTooltips === 'function') {
                    try {
                        const $tips = $(wrapper).find('.tooltip, .tooltipHTML, .tooltipLeft, .tooltipRight, .tooltipBottom');
                        if ($tips.length) { initTooltips($tips); }
                    } catch (e) { /* best-effort */ }
                }

                requestAnimationFrame(() => {
                    wrapper.style.transition = 'opacity 0.3s ease';
                    wrapper.style.opacity = '1';
                });

                if (pushUrl) history.pushState({ ajaxNav: true, url: pushUrl }, '', pushUrl);
                window.scrollTo(0, 0);
            } catch (e) {
                window.location.href = pushUrl;
            }
        }

        document.addEventListener('click', function (e) {
            const a = e.target.closest('a.ajax_nav[data-ajax-url]');
            if (!a) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
            e.preventDefault();
            ajaxSwap(a.getAttribute('data-ajax-url'), a.getAttribute('href'));
        });

        // Back-to-overview button: OGame ufficiale uses href="javascript:void(0)"
        // (no data-back-url attribute — strict 1:1). The destination is derived
        // from the current path: any page under /merchant/* (or /auctioneer)
        // returns to /merchant; the /merchant hub itself returns to /overview.
        function backToOverviewTarget() {
            var p = window.location.pathname;
            if (p === '/merchant' || p === '/merchant/') return '/overview';
            return '/merchant';
        }
        document.addEventListener('click', function (e) {
            const a = e.target.closest('a.js_backToOverview');
            if (!a) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
            e.preventDefault();
            window.location.href = backToOverviewTarget();
        });

        window.addEventListener('popstate', function () {
            window.location.reload();
        });
    })();
</script>
