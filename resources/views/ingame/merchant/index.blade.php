@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="eventboxContent" style="display: none">
        <img height="16" width="16" src="/img/icons/3f9884806436537bdec305aa26fc60.gif">
    </div>
    <div id="traderOverviewcomponent" class="maincontent">
        <div id="traderOverview">
            <div id="inhalt">
                <div id="planet">
                    <div id="detail" class="detail_screen small">
                        <div id="techDetailLoading"></div>
                    </div>
                    <div id="loadingOverlay">
                        <img src="/img/icons/4161a64a933a5345d00cb9fdaa25c7.gif" alt="load...">
                    </div>
                    <div id="header_text">
                        <h2></h2>
                        <a class="back_to_overview js_backToOverview tooltip js_hideTipOnMobile" href="{{ route('overview.index') }}" title="{{ __('t_merchant.back') }}"></a>
                        <a class="small_back_to_overview js_backToOverview tooltip js_hideTipOnMobile" href="{{ route('overview.index') }}" title="{{ __('t_merchant.back') }}"></a>
                    </div>
                    <a href="{{ route('merchant.resource-market') }}" id="js_traderResources" class="js_trader trader_link tooltipLeft js_hideTipOnMobile ipiHintable" data-ipi-hint="ipiTraderResources" data-tooltip-title="{{ __('t_merchant.exchange_resources_desc') }}">
                        <h2>{{ __('t_merchant.resource_market') }}</h2>
                    </a>
                    <a href="{{ route('auctioneer.index') }}" data-ajax-url="{{ route('auctioneer.partial') }}" id="js_traderAuctioneer" class="js_trader trader_link tooltipRight js_hideTipOnMobile ipiHintable ajax_nav" data-ipi-hint="ipiTraderAuctioneer" data-tooltip-title="{{ __('t_merchant.auctioneer_desc') }}">
                        <h2>{{ __('t_merchant.auctioneer') }}</h2>
                    </a>
                    <br>
                    <a href="{{ route('merchant.scrap') }}" id="js_traderScrap" class="js_trader trader_link tooltipLeft js_hideTipOnMobile ipiHintable" data-ipi-hint="ipiTraderScrap" data-tooltip-title="{{ __('t_merchant.scrap_merchant_desc') }}">
                        <h2>{{ __('t_merchant.scrap_merchant') }}</h2>
                    </a>
                    <a href="{{ route('importexport.index') }}" id="js_traderImportExport" class="js_trader trader_link tooltipRight js_hideTipOnMobile ipiHintable" data-ipi-hint="ipiTraderImportExport" data-tooltip-title="{{ __('t_merchant.import_export_desc') }}">
                        <h2>{{ __('t_merchant.import_export') }}</h2>
                    </a>
                </div>
                <div class="c-left"></div>
                <div class="c-right"></div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            // Add hover effects for merchant links
            $('#js_traderResources').hover(
                function() { $(this).addClass('resources_link_hover'); },
                function() { $(this).removeClass('resources_link_hover'); }
            );

            $('#js_traderAuctioneer').hover(
                function() { $(this).addClass('auctioneer_link_hover'); },
                function() { $(this).removeClass('auctioneer_link_hover'); }
            );

            $('#js_traderScrap').hover(
                function() { $(this).addClass('scrap_link_hover'); },
                function() { $(this).removeClass('scrap_link_hover'); }
            );

            $('#js_traderImportExport').hover(
                function() { $(this).addClass('importexport_link_hover'); },
                function() { $(this).removeClass('importexport_link_hover'); }
            );
        });

        (function () {
            const wrapper = document.getElementById('contentWrapper');
            if (!wrapper) return;

            // Slide in from left when returning from auctioneer
            if (sessionStorage.getItem('auctioneer_back')) {
                sessionStorage.removeItem('auctioneer_back');
                wrapper.style.transition = 'none';
                wrapper.style.transform = 'translateX(-50px)';
                wrapper.style.opacity = '0';
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    wrapper.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                    wrapper.style.transform = '';
                    wrapper.style.opacity = '';
                }));
            }

            async function ajaxSwap(url, pushUrl) {
                // Slide current content out to the left
                wrapper.style.transition = 'transform 0.25s ease, opacity 0.25s ease';
                wrapper.style.transform = 'translateX(-50px)';
                wrapper.style.opacity = '0';
                await new Promise(r => setTimeout(r, 260));

                try {
                    const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, cache: 'no-store' });
                    if (!r.ok) { window.location.href = pushUrl; return; }
                    const html = await r.text();

                    // Position new content off-screen to the right before inserting
                    wrapper.style.transition = 'none';
                    wrapper.style.transform = 'translateX(50px)';
                    wrapper.style.opacity = '0';
                    wrapper.innerHTML = html;

                    wrapper.querySelectorAll('script').forEach(old => {
                        const s = document.createElement('script');
                        for (const a of old.attributes) s.setAttribute(a.name, a.value);
                        s.textContent = old.textContent;
                        old.replaceWith(s);
                    });
                    if (typeof initTooltips === 'function') {
                        try {
                            const $tips = $(wrapper).find('.tooltip, .tooltipHTML, .tooltipLeft, .tooltipRight, .tooltipBottom');
                            if ($tips.length) { initTooltips($tips); }
                        } catch(e) {}
                    }

                    // Slide new content in from the right
                    requestAnimationFrame(() => requestAnimationFrame(() => {
                        wrapper.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                        wrapper.style.transform = '';
                        wrapper.style.opacity = '';
                    }));

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

            window.addEventListener('popstate', function () {
                window.location.reload();
            });
        })();
    </script>

@endsection
