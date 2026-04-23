@extends('ingame.layouts.main')

@section('content')

    <style>
        /* PR1: suppress OGame native hover sprite on inventory items (we open detail panel instead) */
        #shop #inhalt .js_invItem a.detail_button,
        #shop #inhalt .js_invItem a.detail_button:hover,
        .no-touch#shop .js_invItem a.detail_button:hover,
        #shop .js_invItem .item_img_box a:hover {
            background: none !important;
        }
    </style>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="eventboxContent" style="display: none">
        <img height="16" width="16" src="/img/icons/3f9884806436537bdec305aa26fc60.gif">
    </div>

    <div id="inhalt">
        <div id="planet">
            <div id="header_text">
                <h2>{{ __('t_shop_items.page_title_shop') }}</h2>
            </div>
            <div id="detail" class="detail_screen small">
                <div id="techDetailLoading"></div>
            </div>
        </div>
        <div class="c-left"></div>
        <div class="c-right"></div>

        <div id="buttonz">
            <div class="header">
                <h2 id="shop_title">{{ __('t_shop_items.page_title_shop') }}</h2>
            </div>
            <div class="content">
                <button class="to_shop active tooltip js_hideTipOnMobile" type="button" data-tab="shop">
                    <span class="to_shop_icon">{{ __('t_shop_items.tab_shop') }}</span>
                </button>
                <button class="to_inventory tooltip js_hideTipOnMobile" type="button" data-tab="inventory">
                    <span class="to_inventory_icon">{{ __('t_shop_items.tab_inventory') }}</span>
                </button>

                <div id="itemBox" class="border5px">
                    <div class="aside">
                        <ul class="listfilter border5px categoryFilter">
                            @foreach($categories as $cat)
                                <li class="border5px inShop inInventory {{ $loop->first ? 'active' : '' }}">
                                    <a href="javascript:void(0);" rel="{{ $cat['ref'] }}" class="js_catLink {{ $loop->first ? 'active' : '' }}" data-category-ref="{{ $cat['ref'] }}">
                                        <span>
                                            {{ __('t_shop_items.' . $cat['lang_key']) }} (<span class="amount">{{ $cat['count'] }}</span>)
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="btn_wrap">
                            <a href="#" tabindex="1" class="btn btn_confirm buyResourcesLink">
                                {{ __('t_shop_items.btn_get_more_resources') }}
                            </a>
                        </div>
                    </div>

                    {{-- Shop slider (coming soon) --}}
                    <div id="js_shopSliderBox" class="shop_slider" style="display:none;">
                        <div style="padding: 40px; text-align: center; color: #9ab;">
                            {{ __('t_shop_items.shop_coming_soon') }}
                        </div>
                    </div>

                    {{-- Inventory slider (server-rendered) --}}
                    <div id="js_inventorySliderBox" class="inventory_slider" style="display:none;">
                        @if (count($inventoryItems) === 0)
                            <div style="padding: 40px; text-align: center; color: #9ab;">
                                {{ __('t_shop_items.inventory_empty') }}
                            </div>
                        @else
                            <div id="inventoryGrid" class="anythingWindow" style="display:flex; flex-wrap:wrap; gap:8px; padding:12px;">
                                @foreach ($inventoryItems as $ref => $item)
                                    @php
                                        $tooltipParts = explode('|', $item['title'], 2);
                                        $tipTitle = $tooltipParts[0] ?? '';
                                        $tipBody = $tooltipParts[1] ?? '';
                                        $imgUrl = '/img/auctioneer/items/' . $item['imageLarge'] . '.png';
                                        $catsAttr = implode(',', $item['category']);
                                        $durSec = (int) ($item['duration_seconds'] ?? 0);
                                        $durLabel = '';
                                        if ($durSec >= 86400 * 7) {
                                            $durLabel = (int) floor($durSec / (86400 * 7)) . 'w';
                                        } elseif ($durSec >= 86400) {
                                            $durLabel = (int) floor($durSec / 86400) . 'd';
                                        } elseif ($durSec >= 3600) {
                                            $durLabel = (int) floor($durSec / 3600) . 'h';
                                        } elseif ($durSec > 0) {
                                            $durLabel = (int) floor($durSec / 60) . 'm';
                                        }
                                    @endphp
                                    <div class="item_img r_{{ $item['rarity'] }} js_invItem"
                                         data-ref="{{ $ref }}"
                                         data-categories="{{ $catsAttr }}"
                                         style="background-image: url({{ $imgUrl }}); cursor: pointer;">
                                        <div class="item_img_box">
                                            <div class="activation enabled"></div>
                                            @if ($durLabel !== '')
                                                <span class="duration">{{ $durLabel }}</span>
                                            @endif
                                            <a href="javascript:void(0);" tabindex="1"
                                               class="detail_button js_invSlideIn"
                                               data-ref="{{ $ref }}">
                                                <span class="ecke"><span class="level amount">{{ $item['amount'] }}</span></span>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="footer"></div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const inventoryItems = @json($inventoryItems);
            const allCategoryRef = @json($allCategoryRef);
            let activateToken = @json($activateToken);
            let currentTab = 'inventory'; // shop is coming-soon in PR1 — default to inventory
            let currentCategory = @json($categories[0]['ref'] ?? null);
            let currentItemRef = null;

            const $ = (sel, root) => (root || document).querySelector(sel);
            const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            }

            const T_ACTIVATE = @json(__('t_shop_items.btn_activate'));
            const T_INVENTORY_EMPTY = @json(__('t_shop_items.inventory_empty'));
            const T_SHOP_COMING = @json(__('t_shop_items.shop_coming_soon'));
            const T_TITLE_SHOP = @json(__('t_shop_items.page_title_shop'));
            const T_TITLE_INV = @json(__('t_shop_items.page_title_inventory'));
            const T_INVENTORY_LABEL = @json(__('t_shop_items.label_inventory'));
            const T_DURATION_LABEL = @json(__('t_shop_items.label_duration'));
            const T_DURATION_INSTANT = @json(__('t_shop_items.duration_instant'));
            const T_BUY_AT_COST = @json(__('t_auctioneer.buy_at_cost'));
            const T_DM_SHORT = @json(__('t_auctioneer.dm_short'));
            const T_NOT_AVAIL = @json(__('t_auctioneer.not_available_yet'));

            function humanizeDuration(sec) {
                sec = Number(sec) || 0;
                if (sec <= 0) return T_DURATION_INSTANT;
                if (sec >= 86400 * 7) return Math.floor(sec / (86400 * 7)) + 'w';
                if (sec >= 86400) return Math.floor(sec / 86400) + 'd';
                if (sec >= 3600) return Math.floor(sec / 3600) + 'h';
                return Math.floor(sec / 60) + 'm';
            }

            function setTab(tab) {
                currentTab = tab;
                $('.to_shop').classList.toggle('active', tab === 'shop');
                $('.to_inventory').classList.toggle('active', tab === 'inventory');
                $('#js_shopSliderBox').style.display = (tab === 'shop') ? 'block' : 'none';
                $('#js_inventorySliderBox').style.display = (tab === 'inventory') ? 'block' : 'none';
                $('#shop_title').textContent = (tab === 'shop') ? T_TITLE_SHOP : T_TITLE_INV;
                // Nell'Inventario, default sulla categoria "tutto" (contiene tutti gli item)
                const defaultCat = (tab === 'inventory') ? allCategoryRef : (@json($categories[0]['ref'] ?? null));
                filterCategory(defaultCat);
                updateCategoryCounts();
            }

            function filterCategory(catRef) {
                currentCategory = catRef;
                $$('.categoryFilter li, .categoryFilter a').forEach(el => el.classList.remove('active'));
                const link = $('.js_catLink[data-category-ref="' + catRef + '"]');
                if (link) { link.classList.add('active'); link.parentElement.classList.add('active'); }
                $$('.js_invItem').forEach(el => {
                    const cats = (el.dataset.categories || '').split(',');
                    el.style.display = cats.indexOf(catRef) !== -1 ? '' : 'none';
                });
            }

            function openDetail(ref) {
                const item = inventoryItems[ref];
                if (!item) return;
                currentItemRef = ref;
                const detailEl = $('#detail');
                const parts = (item.title || '').split('|');
                const title = parts[0] || '';
                const body = item.description_html || (parts[1] || '').split('<br')[0];
                const imgUrl = '/img/auctioneer/items/' + item.imageLarge + '.png';
                const canActivate = item.canBeActivated && item.amount > 0;
                const duration = humanizeDuration(item.duration_seconds);
                detailEl.innerHTML = `
                    <div id="itemDetails" data-uuid="${esc(ref)}">
                        <div class="detailsHolder">
                            <div id="pic"><img src="${esc(imgUrl)}" alt="${esc(title)}"></div>
                            <div id="content">
                                <h2>${esc(title)}</h2>
                                <span class="inventoryAmount">${esc(T_INVENTORY_LABEL)}: <span class="amount">${esc(String(item.amount))}</span></span>
                                <a class="close_details" id="close" href="javascript:void(0);"></a>
                                <br class="clearfloat">
                                <div id="wrapper">
                                    <div id="features">
                                        <p class="extended_description">${body} <span class="more_info blue_txt bold">${esc(T_DURATION_LABEL)}: ${esc(duration)}</span></p>
                                        <a class="build-it_disabled item tooltip js_hideTipOnMobile" title="${esc(T_NOT_AVAIL)}">
                                            <span>${esc(T_BUY_AT_COST)} --- ${esc(T_DM_SHORT)}</span>
                                        </a>
                                        <a class="${canActivate ? 'build-it' : 'build-it_disabled'} item activateItem js_activateItem" href="javascript:void(0);" data-ref="${esc(ref)}">
                                            <span>${esc(T_ACTIVATE)}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                detailEl.classList.add('active');
                detailEl.style.display = 'block';
                const closeBtn = detailEl.querySelector('.close_details');
                if (closeBtn) closeBtn.addEventListener('click', closeDetail);
                const actBtn = detailEl.querySelector('.js_activateItem');
                if (actBtn && canActivate) actBtn.addEventListener('click', () => activate(ref));
                detailEl.querySelectorAll('a.build-it_disabled').forEach(b => {
                    b.addEventListener('click', e => e.preventDefault());
                });
            }

            function closeDetail() {
                currentItemRef = null;
                const detailEl = $('#detail');
                detailEl.classList.remove('active');
                detailEl.style.display = '';
                detailEl.innerHTML = '<div id="techDetailLoading"></div>';
            }

            function fadeMsg(text, isError) {
                if (typeof fadeBox === 'function') { fadeBox(text, !!isError); return; }
                alert(text);
            }

            function activate(ref) {
                const url = '{{ route('shop.activate') }}';
                const fd = new FormData();
                fd.append('ref', ref);
                fd.append('ajax', '1');
                fd.append('token', activateToken);
                fd.append('_token', activateToken);
                fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.error) { fadeMsg((res.message && res.message.message) || res.message || 'Errore', true); return; }
                        if (res.newToken) activateToken = res.newToken;
                        const newAmount = (res.message && res.message.item && typeof res.message.item.amount !== 'undefined')
                            ? res.message.item.amount
                            : (inventoryItems[ref] ? inventoryItems[ref].amount - 1 : 0);
                        if (newAmount <= 0) {
                            delete inventoryItems[ref];
                            const el = document.querySelector('.js_invItem[data-ref="' + ref + '"]');
                            if (el) el.remove();
                            closeDetail();
                            if (Object.keys(inventoryItems).length === 0) {
                                $('#js_inventorySliderBox').innerHTML = '<div style="padding:40px;text-align:center;color:#9ab;">' + esc(T_INVENTORY_EMPTY) + '</div>';
                            }
                        } else {
                            inventoryItems[ref].amount = newAmount;
                            const countEl = document.querySelector('.js_invItem[data-ref="' + ref + '"] .level.amount');
                            if (countEl) countEl.textContent = newAmount;
                            const detEl = document.querySelector('#itemDetails[data-uuid="' + ref + '"] .inventoryAmount .amount');
                            if (detEl) detEl.textContent = newAmount;
                        }
                        fadeMsg((res.message && res.message.message) || 'OK', false);
                        updateCategoryCounts();
                    })
                    .catch(() => fadeMsg('Errore di rete', true));
            }

            function updateCategoryCounts() {
                const counts = {};
                if (currentTab === 'inventory') {
                    Object.values(inventoryItems).forEach(it => {
                        (it.category || []).forEach(c => { counts[c] = (counts[c] || 0) + (it.amount || 0); });
                    });
                }
                $$('.js_catLink').forEach(a => {
                    const ref = a.dataset.categoryRef;
                    const amt = counts[ref] || 0;
                    const span = a.querySelector('.amount');
                    if (span) span.textContent = amt;
                });
            }

            // Wire up (runs immediately — page is injected via AJAX so DOMContentLoaded has already fired)
            function wireUp() {
                $('.to_shop').addEventListener('click', () => setTab('shop'));
                $('.to_inventory').addEventListener('click', () => setTab('inventory'));
                $$('.js_catLink').forEach(a => {
                    a.addEventListener('click', () => filterCategory(a.dataset.categoryRef));
                });
                $$('.js_invItem').forEach(el => {
                    el.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const ref = el.dataset.ref;
                        if (currentItemRef === ref) closeDetail(); else openDetail(ref);
                    }, true);
                });
                setTab('inventory');
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wireUp);
            } else {
                wireUp();
            }
        })();
    </script>

@endsection
