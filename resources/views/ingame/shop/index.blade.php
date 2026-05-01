@extends('ingame.layouts.main')

@section('content')

    <style>
        /* Suppress OGame native hover sprite on item anchors (we open our own detail panel) */
        #shop #inhalt .js_invItem a.detail_button,
        #shop #inhalt .js_invItem a.detail_button:hover,
        .no-touch#shop .js_invItem a.detail_button:hover,
        #shop .js_invItem .item_img_box a:hover,
        #shop .js_shopItem a.detail_button,
        #shop .js_shopItem a.detail_button:hover,
        .no-touch#shop .js_shopItem a.detail_button:hover,
        #shop .js_shopItem .item_img_box a:hover {
            background: none !important;
        }
        #shop .shop_slider { position: relative; }
        /* Grid dimensions match OGame native: 335px wide, centered in the 405px slider
           leaving 35px on each side (30px arrow + 5px gap). */
        #shop .shop_grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 12px 0 40px 0;
            min-height: 300px;
            width: 335px;
            margin: 0 auto;
            box-sizing: border-box;
        }
        /* Override inventory-overlay.css default `.item_img { margin: 2px }`
           which would push 3rd column out of the 335px row. */
        #shop .shop_grid .js_shopItem.item_img { margin: 0; }

        /* Shop slider side arrows (« / ») — native OGame layout
           Replicates exactly the live computed style:
           span.arrow: position:absolute; top:50%; bottom:90px; width:30px; height:90px
           a inside: 30x180 with margin-top:-90 (visual centering)
           Sprite: same buff sprite, positions -30px 0px (back) / -120px 0px (forward) */
        #shop .shop_slider .arrow {
            position: absolute;
            top: 50%;
            display: block;
            background: none;
            margin: 0; padding: 0; border: 0;
            color: #f1f1f1;
            z-index: 5;
            width: 30px;
            height: 90px;
        }
        #shop .shop_slider .arrow.back { left: 0; }
        #shop .shop_slider .arrow.forward { right: 0; }
        #shop .shop_slider .arrow a {
            display: block;
            width: 30px;
            height: 180px;
            margin: -90px 0 0;
            background: url('/img/inventory/buff_sprite.png') no-repeat;
            cursor: pointer;
            outline: 0;
        }
        #shop .shop_slider .arrow.back a { background-position: -30px 0; }
        #shop .shop_slider .arrow.forward a { background-position: -120px 0; }
        #shop .shop_slider .arrow.back a:hover { background-position: 0 0; }
        #shop .shop_slider .arrow.forward a:hover { background-position: -150px 0; }
        #shop .shop_slider .arrow.disabled a { opacity: 0.4; cursor: default; }
        #shop .shop_slider .arrow span { display: none; }
        #shop .shop_nav { position: relative; height: 28px; margin-top: 4px; }
        #shop .shop_nav .arrow { position: absolute; top: 0; width: 28px; height: 28px; display: block; background: url('/img/layout/slider-arrows.png') no-repeat; cursor: pointer; }
        #shop .shop_nav .arrow.back { left: 8px; background-position: 0 0; }
        #shop .shop_nav .arrow.forward { right: 8px; background-position: -28px 0; }
        #shop .shop_nav .arrow.disabled { opacity: 0.35; cursor: default; }
        #shop .shop_nav .arrow a { display: block; width: 28px; height: 28px; color: #fff; text-decoration: none; text-align: center; line-height: 26px; font-size: 20px; font-weight: bold; }
        #shop #detail #pic img { width: 200px; height: 200px; }
        #shop #detail .inventoryAmount { margin: 0; padding: 0; border: 0; z-index: 1; width: auto; position: absolute; right: 35px; font: 10px/22px Verdana, Arial, Helvetica, sans-serif; color: #d29d00; }
        #shop #detail #description { padding: 0; border: 0; font-weight: inherit; font-style: inherit; font-family: inherit; z-index: 1; color: #848484; height: 90px; line-height: 130%; margin: 3px 0 0 4px; }
        #shop #detail #description p { line-height: 130%; margin: 0; padding: 0; border: 0; font-style: inherit; font-size: 100%; font-family: inherit; z-index: 1; white-space: normal; color: #6F9FC8; font-weight: 700; }
        #shop #detail #description a.help { line-height: 130%; padding: 0; border: 0; font-weight: inherit; font-style: inherit; font-size: 100%; font-family: inherit; z-index: 1; color: #848484; display: block; float: left; height: 18px; margin: 2px 5px 0 0; width: 18px; background: transparent url('//gf3.geo.gfsrv.net/cdneb/f5f81e8302aaad56c958c033677fb8.png') -207px -34px no-repeat; }
        /* Native OGame label inside the buy buttons (#itemDetails .build-it span.textlabel)
           Verified live computed style (Apr 2026):
             font: 700 12px/14px Verdana, Arial, Helvetica, sans-serif (BOLD 12px)
             color: #fff when parent .build-it / #848484 when .build-it_disabled
             display: table-cell; vertical-align: middle; width:143; height:54 */
        #shop #detail #itemDetails .textlabel,
        #shop #detail #itemDetails span.textlabel {
            font: 700 12px/14px Verdana, Arial, Helvetica, sans-serif !important;
            text-align: center !important;
            cursor: default;
            margin: 0;
            padding: 0;
            border: 0;
            display: table-cell !important;
            vertical-align: middle !important;
            width: 143px !important;
            height: 54px !important;
        }
        #shop #detail #itemDetails a.build-it .textlabel { color: #fff !important; }
        #shop #detail #itemDetails a.build-it_disabled .textlabel { color: #848484 !important; }
        #shop #detail #itemDetails .specialPrice em { color: #d43635; text-decoration: line-through; font-style: normal; font-weight: bold; }
        /* Native OGame warning span — used in detail panel descriptions when an action cannot be performed
           (e.g. storage full for Pacchetto Risorse completo). Icon is at left, grey text inheriting parent. */
        #shop #detail .warningSign,
        #shop .extended_description .warningSign {
            color: #848484;
            font: 11px Verdana, Arial, Helvetica, sans-serif;
            line-height: 14px;
            white-space: normal;
            margin: 0;
            padding: 0;
            border: 0;
            font-weight: inherit;
            font-style: inherit;
            font-size: 100%;
            font-family: inherit;
            z-index: 1;
            background: url('/img/icons/warning_sign.gif') no-repeat;
            background-size: 20px;
            display: inline-block;
            margin-top: 5px;
            padding-left: 25px;
        }
        #shop .btn_wrap { margin: 0 2px; padding: 0; border: 0; }
        #shop .btn_wrap .btn { display: block; margin-bottom: 4px; }
        /* Remove extra spacing under sidebar category list (caused buttons to drop too low) */
        #shop ul.listfilter { margin-bottom: 0; padding-bottom: 0; }
        /* Native OGame button style for "Procura risorse" + "Acquista MO" */
        #shop .btn_wrap .btn.btn_confirm {
            margin: 0;
            border: 0;
            background-color: #000;
            background-image: url("//gf3.geo.gfsrv.net/cdnea/8c3657fbb2a8808627f8a3dbedcb8c.png");
            background-repeat: repeat-x;
            background-position: 0 -228px;
            height: 38px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            padding: 0 16px;
            position: relative;
            text-shadow: 0 0 1px #000;
            color: #fff;
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            line-height: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #shop .btn_wrap .btn.btn_confirm + .btn.btn_confirm { margin-top: 4px; }

        /* Native OGame paginator (#shopPaginator) — replicates exact computed styles
           captured from live OGame (cdn /shop "tutto" category, Apr 2026). */
        #shop .shopPaginator {
            position: relative;
            bottom: 26px;
            display: flex;
            justify-content: center;
            margin: 0;
            padding: 0;
            border: 0;
            color: #f1f1f1;
            font: 12px/12px Verdana, Arial, sans-serif;
            height: 30px;
            background: transparent;
        }
        #shop .shopPaginator .arrowPrev,
        #shop .shopPaginator .arrowNext {
            display: block;
            background: rgb(52, 65, 79);
            color: rgb(132, 132, 132);
            border: 0;
            border-radius: 0;
            font-family: Arial;
            font-size: 13.3333px;
            font-weight: 400;
            padding: 3px 8px 1px;
            margin: 5px 3px;
            height: 20px;
            cursor: default;
        }
        #shop .shopPaginator #shopPages { display: flex; align-items: center; }
        #shop .shopPaginator ul.panelShop {
            display: flex;
            flex-direction: row;
            list-style: none;
            margin: 0;
            padding: 0;
            font: 12px Verdana, Arial, sans-serif;
        }
        #shop .shopPaginator ul.panelShop button {
            display: flex;
            align-items: center;
            background: rgb(52, 65, 79);
            color: rgb(132, 132, 132);
            border: 0;
            border-radius: 0;
            font-family: Arial;
            font-size: 14px;
            font-weight: 400;
            margin: 5px;
            padding: 10px;
            height: 20px;
            cursor: default;
        }
        #shop .shopPaginator ul.panelShop button.activePager {
            background: rgb(70, 80, 89);
            color: #fff;
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
                        {{-- Sidebar: shop categories (visible on shop tab) --}}
                        <ul class="listfilter border5px categoryFilter js_shopCatList">
                            @foreach($shopCatalog['categories'] as $i => $cat)
                                <li class="border5px inShop {{ $i === 0 ? 'active' : '' }}">
                                    <a href="javascript:void(0);" rel="{{ $cat['key'] }}" class="js_shopCatLink {{ $i === 0 ? 'active' : '' }}" data-shop-category="{{ $cat['key'] }}">
                                        <span>
                                            {{ $cat['name'] }} (<span class="amount">{{ $cat['count'] }}</span>)
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Sidebar: inventory categories (visible on inventory tab) --}}
                        <ul class="listfilter border5px categoryFilter js_invCatList">
                            @foreach($categories as $cat)
                                <li class="border5px inInventory {{ $loop->first ? 'active' : '' }}">
                                    <a href="javascript:void(0);" rel="{{ $cat['ref'] }}" class="js_catLink {{ $loop->first ? 'active' : '' }}" data-category-ref="{{ $cat['ref'] }}">
                                        <span>
                                            {{ __('t_shop_items.' . $cat['lang_key']) }} (<span class="amount">{{ $cat['count'] }}</span>)
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <span id="js_dmBalance" style="display:none;">{{ number_format($darkMatter, 0, ',', '.') }}</span>
                        <div class="btn_wrap">
                            <a href="#" tabindex="1" class="btn btn_confirm buyResourcesLink">
                                {{ __('t_shop_items.btn_get_more_resources') }}
                            </a>
                            <a href="{{ route('payment.overlay') }}" tabindex="1" class="btn btn_confirm buyDarkMatterLink overlay"
                               data-overlay-popup-width="800" data-overlay-popup-height="620">
                                {{ __('t_shop_items.btn_purchase_dark_matter') }}
                            </a>
                        </div>
                    </div>

                    {{-- Shop slider (all items rendered; toggled/paginated in JS) --}}
                    <div id="js_shopSliderBox" class="shop_slider" style="display:none; position:relative;">
                        <span class="arrow back" id="js_shopArrowBack"><a href="javascript:void(0);"><span>&laquo;</span></a></span>
                        <span class="arrow forward" id="js_shopArrowForward"><a href="javascript:void(0);"><span>&raquo;</span></a></span>
                        <div id="shopGrid" class="shop_grid anythingWindow">
                            @foreach ($shopCatalog['all_items'] as $ref => $item)
                                @php
                                    $cats = implode(',', $item['categories']);
                                    $durText = $item['duration_label'] ?: __('t_shop_items.duration_instant');
                                    // Native tooltip format: "NAME|EFFECT<br /><br />Durata: X<br /><br />Prezzo: Y<br />Nell'inventario: 0"
                                    $effect = $item['effect_description'] ?: ($item['description'] ?? '');
                                    $tipTitle = $item['name'];
                                    $tipBody = e($effect) . '<br /><br />'
                                        . __('t_shop_items.label_duration') . ': ' . e($durText) . '<br /><br />'
                                        . __('t_shop_items.label_price') . ': ' . e($item['price_label']) . ' ' . __('t_shop_items.dm_short') . '<br />'
                                        . __('t_shop_items.label_inventory') . ': 0';
                                    // Primary + fallback images for background (native pattern)
                                    $bg = 'url(' . $item['image_url'] . ')';
                                    if (!empty($item['image_fallback_url'])) {
                                        $bg .= ', url(' . $item['image_fallback_url'] . ')';
                                    }
                                @endphp
                                <div class="item_img r_{{ $item['rarity'] }} js_shopItem"
                                     data-ref="{{ $item['ref'] }}"
                                     data-shop-cats="{{ $cats }}"
                                     style="background-image: {{ $bg }};">
                                    <div class="item_img_box">
                                        <div class="activation disabled"></div>
                                        <a href="javascript:void(0);" tabindex="1"
                                           class="detail_button tooltipHTML js_hideTipOnMobile slideIn"
                                           ref="{{ $item['ref'] }}"
                                           title="{{ $tipTitle }}|{!! $tipBody !!}">
                                            <span class="ecke"><span class="level price">{{ $item['price_label'] }}</span></span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @php
                            // Compute initial page count for the active "all" category (will be re-derived by JS on category change)
                            $initialItemCount = count($shopCatalog['all_items']);
                            $totalPages = max(1, (int) ceil($initialItemCount / \OGame\Services\ShopService::ITEMS_PER_PAGE));
                        @endphp
                        <div class="shopPaginator" id="shopPaginator">
                            <button type="button" class="arrowPrev" data-action="prev">&laquo;</button>
                            <div id="shopPages" class="shopPages">
                                <ul id="panelShop" class="panelShop">
                                    @for ($i = 0; $i < $totalPages; $i++)
                                        <button type="button" data-page="{{ $i }}" class="{{ $i === 0 ? 'activePager' : '' }}" style="display:flex;align-items:center;margin:5px;padding:10px;font-size:14px;{{ $i === 0 ? 'background-color:rgb(70,80,89);color:rgb(255,255,255);' : '' }}">{{ $i + 1 }}</button>
                                    @endfor
                                </ul>
                            </div>
                            <button type="button" class="arrowNext" data-action="next">&raquo;</button>
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
                                        $imgUrl = $item['image_override_url'] ?? ('/img/auctioneer/items/' . $item['imageLarge'] . '.png');
                                        $catsAttr = implode(',', $item['category']);
                                        // Native OGame shows the duration badge only for long-running timed
                                        // items (≥ 1 day). Short-instant items (1h packs, classes, etc.) get no badge.
                                        $durSec = (int) ($item['duration_seconds'] ?? 0);
                                        $durLabel = '';
                                        if ($durSec >= 86400 * 7) {
                                            $durLabel = (int) floor($durSec / (86400 * 7)) . 'w';
                                        } elseif ($durSec >= 86400) {
                                            $durLabel = (int) floor($durSec / 86400) . 'd';
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
                                               title="{{ $tipTitle }}|{{ $tipBody }}"
                                               class="detail_button tooltipHTML js_hideTipOnMobile js_invSlideIn"
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
            const shopItemsByRef = @json($shopCatalog['all_items']);
            const shopCategories = @json(array_map(fn($c) => $c['key'], $shopCatalog['categories']));
            const shopItemsPerPage = {{ \OGame\Services\ShopService::ITEMS_PER_PAGE }};
            const allCategoryRef = @json($allCategoryRef);
            let activateToken = @json($activateToken);
            let currentTab = 'shop';
            let currentCategory = @json($categories[0]['ref'] ?? null);
            const ALL_CAT_KEY = @json(\OGame\Services\ShopService::ALL_CATEGORY_KEY);
            // Default to "tutto" — last item in shopCategories
            let currentShopCategory = ALL_CAT_KEY;
            let currentShopPage = 0;
            let currentItemRef = null;
            let dmBalance = {{ (int) $darkMatter }};

            const $ = (sel, root) => (root || document).querySelector(sel);
            const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            }

            function fmtInt(n) { return (Number(n) || 0).toLocaleString('it-IT'); }

            const T_ACTIVATE = @json(__('t_shop_items.btn_activate'));
            const T_BUY = @json(__('t_shop_items.btn_buy'));
            const T_CONFIRM_BUY = @json(__('t_shop_items.btn_confirm_buy'));
            const T_INVENTORY_EMPTY = @json(__('t_shop_items.inventory_empty'));
            const T_TITLE_SHOP = @json(__('t_shop_items.page_title_shop'));
            const T_TITLE_INV = @json(__('t_shop_items.page_title_inventory'));
            const T_INVENTORY_LABEL = @json(__('t_shop_items.label_inventory'));
            const T_INVENTORY_SHORT = @json(__('t_shop_items.label_inventory_short'));
            const T_RULES_TITLE = @json(__('t_shop_items.tooltip_rules_title'));
            const T_DURATION_LABEL = @json(__('t_shop_items.label_duration'));
            const T_DURATION_INSTANT = @json(__('t_shop_items.duration_instant'));
            const T_PRICE = @json(__('t_shop_items.price'));
            const T_DM = @json(__('t_shop_items.label_dark_matter'));
            const T_INSUFFICIENT_DM = @json(__('t_shop_items.buy_insufficient_dm'));
            const T_BUY_AT_COST = @json(__('t_shop_items.btn_buy_at_cost'));
            const T_BUY_AND_ACTIVATE = @json(__('t_shop_items.btn_buy_and_activate'));

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
                $('.js_shopCatList').style.display = (tab === 'shop') ? '' : 'none';
                $('.js_invCatList').style.display = (tab === 'inventory') ? '' : 'none';
                $('#shop_title').textContent = (tab === 'shop') ? T_TITLE_SHOP : T_TITLE_INV;
                closeDetail();
                if (tab === 'shop') {
                    filterShopCategory(currentShopCategory);
                } else {
                    const defaultCat = allCategoryRef;
                    filterCategory(defaultCat);
                }
                updateCategoryCounts();
            }

            // ----- Shop tab -----
            function filterShopCategory(catKey) {
                currentShopCategory = catKey;
                currentShopPage = 0;
                $$('.js_shopCatList li, .js_shopCatList a').forEach(el => el.classList.remove('active'));
                const link = $('.js_shopCatLink[data-shop-category="' + catKey + '"]');
                if (link) { link.classList.add('active'); link.parentElement.classList.add('active'); }
                renderShopPage();
            }

            function visibleShopItems() {
                if (currentShopCategory === ALL_CAT_KEY) {
                    return $$('.js_shopItem');
                }
                return $$('.js_shopItem').filter(el => {
                    const cats = (el.dataset.shopCats || '').split(',');
                    return cats.indexOf(currentShopCategory) !== -1;
                });
            }

            function renderShopPage() {
                const items = visibleShopItems();
                const totalPages = Math.max(1, Math.ceil(items.length / shopItemsPerPage));
                if (currentShopPage >= totalPages) currentShopPage = totalPages - 1;
                if (currentShopPage < 0) currentShopPage = 0;
                const start = currentShopPage * shopItemsPerPage;
                const end = start + shopItemsPerPage;
                // Hide every shop item, then show only this page's slice
                $$('.js_shopItem').forEach(el => { el.style.display = 'none'; });
                items.slice(start, end).forEach(el => { el.style.display = ''; });
                renderShopPager(totalPages);
            }

            function renderShopPager(totalPages) {
                const pager = $('#shopPaginator');
                if (!pager) return;
                const ul = pager.querySelector('#panelShop');
                const arrPrev = pager.querySelector('.arrowPrev');
                const arrNext = pager.querySelector('.arrowNext');
                if (!ul) return;
                // Re-render buttons if the count changed (category switch can resize pages)
                if (ul.children.length !== totalPages) {
                    ul.innerHTML = '';
                    for (let i = 0; i < totalPages; i++) {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.dataset.page = String(i);
                        b.textContent = String(i + 1);
                        b.style.cssText = 'display:flex;align-items:center;margin:5px;padding:10px;font-size:14px;';
                        ul.appendChild(b);
                    }
                }
                // Rolling window: show only 5 buttons centered on current page
                const windowSize = 5;
                let start = Math.max(0, currentShopPage - Math.floor(windowSize / 2));
                let end = Math.min(totalPages - 1, start + windowSize - 1);
                if (end - start + 1 < windowSize) start = Math.max(0, end - windowSize + 1);
                Array.from(ul.children).forEach((b) => {
                    const p = parseInt(b.dataset.page, 10);
                    const active = p === currentShopPage;
                    const visible = p >= start && p <= end;
                    b.style.display = visible ? 'flex' : 'none';
                    b.classList.toggle('activePager', active);
                    b.style.backgroundColor = active ? 'rgb(70,80,89)' : '';
                    b.style.color = active ? 'rgb(255,255,255)' : '';
                });
                const prevDisabled = currentShopPage === 0 || totalPages <= 1;
                const nextDisabled = currentShopPage >= totalPages - 1 || totalPages <= 1;
                if (arrPrev) arrPrev.disabled = prevDisabled;
                if (arrNext) arrNext.disabled = nextDisabled;
                // Sync side arrows on the slider edges
                const sideBack = document.getElementById('js_shopArrowBack');
                const sideFwd = document.getElementById('js_shopArrowForward');
                if (sideBack) sideBack.classList.toggle('disabled', prevDisabled);
                if (sideFwd) sideFwd.classList.toggle('disabled', nextDisabled);
            }

            function wireShopArrows() {
                const pager = $('#shopPaginator');
                if (!pager) return;
                const arrPrev = pager.querySelector('.arrowPrev');
                const arrNext = pager.querySelector('.arrowNext');
                const ul = pager.querySelector('#panelShop');
                if (arrPrev) arrPrev.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (arrPrev.disabled) return;
                    if (currentShopPage > 0) { currentShopPage--; renderShopPage(); }
                });
                if (arrNext) arrNext.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (arrNext.disabled) return;
                    currentShopPage++; renderShopPage();
                });
                if (ul) ul.addEventListener('click', (e) => {
                    const btn = e.target.closest('button[data-page]');
                    if (!btn) return;
                    e.preventDefault();
                    currentShopPage = parseInt(btn.dataset.page, 10);
                    renderShopPage();
                });

                // Side arrows on the slider edges
                const sideBack = $('#js_shopArrowBack');
                const sideFwd = $('#js_shopArrowForward');
                if (sideBack) sideBack.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (sideBack.classList.contains('disabled')) return;
                    if (currentShopPage > 0) { currentShopPage--; renderShopPage(); }
                });
                if (sideFwd) sideFwd.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (sideFwd.classList.contains('disabled')) return;
                    currentShopPage++; renderShopPage();
                });
            }

            // ----- Inventory tab -----
            function filterCategory(catRef) {
                currentCategory = catRef;
                $$('.js_invCatList li, .js_invCatList a').forEach(el => el.classList.remove('active'));
                const link = $('.js_catLink[data-category-ref="' + catRef + '"]');
                if (link) { link.classList.add('active'); link.parentElement.classList.add('active'); }
                $$('.js_invItem').forEach(el => {
                    const cats = (el.dataset.categories || '').split(',');
                    el.style.display = cats.indexOf(catRef) !== -1 ? '' : 'none';
                });
            }

            // ----- Detail panel -----
            function openInventoryDetail(ref) {
                const item = inventoryItems[ref];
                if (!item) return;
                currentItemRef = ref;
                const detailEl = $('#detail');
                const parts = (item.title || '').split('|');
                const title = parts[0] || '';
                const body = item.description_ext || item.description_html || (parts[1] || '').split('<br')[0];
                const imgUrl = item.image_override_url || ('/img/auctioneer/items/' + item.imageLarge + '.png');
                const canActivate = item.canBeActivated && item.amount > 0;
                // Prefer scraped duration_label ("ora", "Permanente"...) over auto-format.
                const duration = item.duration_label || humanizeDuration(item.duration_seconds);
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

            function openShopDetail(ref) {
                const item = shopItemsByRef[ref];
                if (!item) return;
                currentItemRef = ref;
                const detailEl = $('#detail');
                const duration = item.duration_label || T_DURATION_INSTANT;
                const canAfford = dmBalance >= item.price_dm;
                const invCount = 0; // live API returns this; for initial render we use 0
                const boldifyReductions = (escapedText) => escapedText.replace(/\(([-+])([^)]{1,8})\)/g, '($1<span style="font-weight: bold;">$2</span>)');
                const ext = boldifyReductions(esc(item.extended_description || item.description || ''));
                const effect = boldifyReductions(esc(item.effect_description || item.description || ''));
                const hasOriginal = item.price_dm_original && item.price_label_original;

                // Format price as full number with thousands separator (Italian: dot)
                // Native OGame shows "500.000 MO" not the abbreviated "500K MO".
                const fmtPrice = (n) => {
                    const v = parseInt(n, 10);
                    if (!v) return '0';
                    return v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                };
                const priceFull = fmtPrice(item.price_dm) + ' MO';
                const priceOrigFull = item.price_dm_original ? fmtPrice(item.price_dm_original) + ' MO' : '';

                const priceBlock = hasOriginal
                    ? `<span class="textlabel specialPrice">
                         ${esc(T_BUY_AT_COST)}:<br>
                         <span><em>${esc(priceOrigFull)}</em><br>${esc(priceFull)}</span>
                       </span>`
                    : `<span class="textlabel">${esc(T_BUY_AT_COST)}:<br>${esc(priceFull)}</span>`;
                // Show "Compra & Attiva" for any item that is activatable (not just offerte_speciali).
                // Permanent items (planet fields, avatars, class selection) only show "Compra al costo di".
                const isActivatable = !(Array.isArray(item.categories) && (
                    item.categories.includes('profilo') || item.categories.includes('seleziona_classe')
                ));
                const showBuyAndActivate = isActivatable;

                detailEl.innerHTML = `
                    <div id="itemDetails" data-uuid="${esc(ref)}">
                        <div class="detailsHolder">
                            <div id="pic"><img src="${esc(item.image_url)}" width="200" height="200" alt=""></div>
                            <div id="content">
                                <h2>${esc(item.name)}</h2>
                                <span class="inventoryAmount">${esc(T_INVENTORY_SHORT)}: <span class="amount">${invCount}</span></span>
                                <a class="close_details" id="close" href="javascript:void(0);"></a>
                                <br class="clearfloat">
                                <div id="wrapper">
                                    <div id="features">
                                        <p class="extended_description">${ext}<span class="more_info blue_txt bold">${esc(T_DURATION_LABEL)}: ${esc(duration)}</span></p>
                                        <a class="${canAfford ? 'build-it' : 'build-it_disabled'} item dm tooltip js_hideTipOnMobile js_buyItem" href="javascript:void(0);" data-ref="${esc(ref)}">
                                            ${priceBlock}
                                        </a>
                                        ${showBuyAndActivate ? `<a class="${canAfford ? 'build-it' : 'build-it_disabled'} buyAndActivate dm tooltip js_hideTipOnMobile js_buyAndActivate" href="javascript:void(0);" data-ref="${esc(ref)}">
                                            <span class="textlabel">${esc(T_BUY_AND_ACTIVATE)}</span>
                                        </a>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="description">
                            ${item.rules_description ? `<a href="javascript:void(0);" class="tooltipHTML help" title="${esc(T_RULES_TITLE)}|${esc(item.rules_description)}"></a>` : ''}
                            <p>${effect}</p>
                        </div>
                    </div>`;
                detailEl.classList.add('active');
                detailEl.style.display = 'block';
                const closeBtn = detailEl.querySelector('.close_details');
                if (closeBtn) closeBtn.addEventListener('click', closeDetail);
                const buyBtn = detailEl.querySelector('.js_buyItem');
                if (buyBtn && canAfford) buyBtn.addEventListener('click', () => buyShopItem(ref, false));
                const buyActBtn = detailEl.querySelector('.js_buyAndActivate');
                if (buyActBtn && canAfford) buyActBtn.addEventListener('click', () => buyShopItem(ref, true));
                detailEl.querySelectorAll('a.build-it_disabled').forEach(b => {
                    b.addEventListener('click', e => e.preventDefault());
                });
                if (typeof window.initTooltips === 'function' && typeof window.jQuery === 'function') {
                    try { window.initTooltips(window.jQuery(detailEl)); } catch (e) {}
                }
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

            // ----- Actions -----
            function buyShopItem(ref, activateAfter) {
                if (!confirm(T_CONFIRM_BUY + '?')) return;
                const url = '{{ route('shop.buy') }}';
                const fd = new FormData();
                fd.append('ref', ref);
                fd.append('ajax', '1');
                fd.append('token', activateToken);
                fd.append('_token', activateToken);
                fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                        if (res.error) { fadeMsg(res.message || T_INSUFFICIENT_DM, true); return; }
                        if (res.newToken) activateToken = res.newToken;
                        if (typeof res.dark_matter !== 'undefined') {
                            dmBalance = res.dark_matter;
                            const dmEl = $('#js_dmBalance');
                            if (dmEl) dmEl.textContent = fmtInt(dmBalance);
                            // update top-bar DM if present
                            const topDm = document.getElementById('resources_darkmatter');
                            if (topDm) topDm.textContent = fmtInt(dmBalance);
                        }
                        fadeMsg(res.message || 'OK', false);
                        if (activateAfter) {
                            setTimeout(() => activate(ref), 200);
                            return;
                        }
                        closeDetail();
                        setTimeout(() => { window.location.reload(); }, 600);
                    })
                    .catch(() => fadeMsg('Errore di rete', true));
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
                if (currentTab !== 'inventory') return;
                const counts = {};
                Object.values(inventoryItems).forEach(it => {
                    (it.category || []).forEach(c => { counts[c] = (counts[c] || 0) + (it.amount || 0); });
                });
                $$('.js_catLink').forEach(a => {
                    const ref = a.dataset.categoryRef;
                    const amt = counts[ref] || 0;
                    const span = a.querySelector('.amount');
                    if (span) span.textContent = amt;
                });
            }

            function wireUp() {
                $('.to_shop').addEventListener('click', () => setTab('shop'));
                $('.to_inventory').addEventListener('click', () => setTab('inventory'));
                $$('.js_catLink').forEach(a => {
                    a.addEventListener('click', () => filterCategory(a.dataset.categoryRef));
                });
                $$('.js_shopCatLink').forEach(a => {
                    a.addEventListener('click', () => filterShopCategory(a.dataset.shopCategory));
                });
                $$('.js_invItem').forEach(el => {
                    el.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const ref = el.dataset.ref;
                        if (currentItemRef === ref) closeDetail(); else openInventoryDetail(ref);
                    }, true);
                });
                $$('.js_shopItem').forEach(el => {
                    el.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const ref = el.dataset.ref;
                        if (currentItemRef === ref) closeDetail(); else openShopDetail(ref);
                    }, true);
                });
                wireShopArrows();
                setTab('shop');
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wireUp);
            } else {
                wireUp();
            }
        })();
    </script>

@endsection
