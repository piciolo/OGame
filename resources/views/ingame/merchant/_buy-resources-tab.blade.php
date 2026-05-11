{{--
    Tab "Procura risorse" — replica fedele del pannello #tabs-buyResource di OGame
    ufficiale (vedi _research/trader_resources/ANALYSIS.md per lo schema completo).

    Variabili in input (passate da MerchantController):
        $darkMatter   int   Saldo MO inventario player
        $buyPackages  array {
            metal:     {amount, daily_production, storage_headroom, is_capped, cost_dm, sufficient_dm},
            crystal:   {... uguale},
            deuterium: {... uguale},
            all:       {amount, is_capped, cost_dm, sufficient_dm, packages: {...}}
        }

    Convenzioni di markup OGame (preservate per fedeltà visuale e compatibilità JS):
      - Wrapper .roundBox.fillup.{resourceKey}[.disabled]
      - Per box "single": 1 .resource_box (img + input)
      - Per box "all": 3 .resource_box (uno per risorsa)
      - .sale_badge.disabled  → placeholder eventi sconto (mai attivo per ora)
      - .resource_img         → sfondo immagine pacchetto (CSS in app)
      - .tooltip.cappedToolTip → mostrato solo se la risorsa è cappata su quel box
      - input ha min="0", max="<daily>", aria-valuemin/max, data-* di tracking
      - .fillup_txt + .fillup_cost + .clearfix.fill_resource_ctn + .btn_wrap > a.btn_blue
      - Bottone label: "Riempire risorse?" (testo OGame), aria-label="<resource> Riempire risorse?"
      - data-itemuuid: identificatore stabile per pacchetto+pianeta (sha1 deterministico)
      - data-trader-buy-resource: token CSRF-style per request (rigenerato a ogni render)
--}}

@php
    $resTypeId = ['metal' => 1, 'crystal' => 2, 'deuterium' => 3, 'allLocalResources' => 1];

    // Per-request token used by OGame ufficiale on every buy button. We generate
    // one per render so the value is unique per page-load (matches the live page);
    // server-side anti-tamper does not need to validate this — server recomputes
    // amount and price from authoritative state.
    $traderBuyToken = bin2hex(random_bytes(16));

    $planetId = optional(auth()->user())->planet_current ?? 0;

    // Build per-resource render context. Unique data-itemuuid is sha1 of stable
    // inputs so it stays consistent across reloads of the same package on the
    // same planet (mirrors OGame's behaviour).
    $buildBoxContext = function (string $packageKey, string $resourceKey, array $pkg) use ($resTypeId, $planetId) {
        $dailyProduction = (int) ($pkg['daily_production']
            ?? array_sum(array_column($pkg['packages'] ?? [], 'daily_production')));
        $deliverableAmount = (int) ($pkg['amount']
            ?? array_sum(array_column($pkg['packages'] ?? [], 'amount')));
        $costDm = (int) ($pkg['cost_dm'] ?? 0);
        $sufficient = !empty($pkg['sufficient_dm']);
        $isCapped = !empty($pkg['is_capped']);

        // Fully unbuyable: nothing the player can actually receive (production AND
        // storage both fully exhausted). The sub-inputs already render 0 via the
        // closure's $fullyDisabled path — we mirror that on the outer fillup_cost
        // and on the button cost so the box is internally consistent (no "0 unità
        // / 500 MO" mismatch).
        $fullyUnbuyable = $dailyProduction <= 0 || $deliverableAmount <= 0;
        // OGame ufficiale: when there IS something to buy but the player doesn't
        // have enough Dark Matter, the box gets a `premium` class (cosmetic) and
        // the button morphs to "Compra adesso" linking to the DM shop.
        $premiumState = !$fullyUnbuyable && !$sufficient;

        $boxClasses = ['roundBox', 'fillup', $resourceKey];
        if ($fullyUnbuyable || !$sufficient) {
            $boxClasses[] = 'disabled';
        }
        if ($premiumState) {
            $boxClasses[] = 'premium';
        }

        return [
            'box_classes'      => implode(' ', $boxClasses),
            'package_key'      => $packageKey,
            'resource_key'     => $resourceKey,
            'resource_type_id' => $resTypeId[$packageKey],
            'daily_production' => $dailyProduction,
            // Real cost on data-* attributes (anti-tamper not needed but matches OGame).
            'cost_dm'          => $fullyUnbuyable ? 0 : $costDm,
            // Displayed cost in the fillup_cost span: OGame teases the cheapest entry
            // (MIN_COST_DM, e.g. 500 MO) when the box is in premium upsell state,
            // otherwise shows the real package price.
            'cost_dm_display'  => $premiumState
                ? \OGame\Services\BuyResourcesService::MIN_COST_DM
                : ($fullyUnbuyable ? 0 : $costDm),
            // Overmark on the cost span only when capped AND still buyable (OGame
            // hides it in the premium upsell state too).
            'is_capped'        => ($isCapped && !$fullyUnbuyable && !$premiumState) ? '1' : '0',
            'sufficient_dm'    => $sufficient ? '1' : '0',
            'premium_state'    => $premiumState,
            'item_uuid'        => sha1('procura|'.$packageKey.'|planet:'.$planetId),
            'sub_resources'    => isset($pkg['packages']) ? array_keys($pkg['packages']) : [],
            'sub_packages'     => $pkg['packages'] ?? [],
        ];
    };

    $boxes = [
        $buildBoxContext('metal',             'metal',             $buyPackages['metal']),
        $buildBoxContext('crystal',           'crystal',           $buyPackages['crystal']),
        $buildBoxContext('deuterium',         'deuterium',         $buyPackages['deuterium']),
        $buildBoxContext('allLocalResources', 'allLocalResources', $buyPackages['all']),
    ];

    // Reusable closure that emits the .resource_box subtree (img + input) for a
    // single resource. Used once for single-resource boxes, three times in a row
    // for the "allLocalResources" composite box.
    // $offerable = 0 (zero production OR zero headroom) means the package is
    // completely unbuyable → OGame ufficiale renders the input with the disabled
    // attribute, value 0, dark grey CSS. Capped (headroom < daily but > 0) is a
    // softer state: input editable up to daily, overmark CSS on the cost.
    $renderResourceBox = function (string $resourceKey, int $dailyProduction, int $costDm, bool $isCapped, int $offerable = -1) use ($resTypeId) {
        $resourceTypeId = $resTypeId[$resourceKey] ?? 1;
        // If caller didn't supply an explicit offerable (legacy callers), default
        // to daily_production (= unbounded) to preserve previous behaviour.
        if ($offerable < 0) { $offerable = $dailyProduction; }
        $fullyDisabled = ($dailyProduction <= 0 || $offerable <= 0);

        $inputClasses = 'right checkThousandSeparator' . ($isCapped ? ' overmark' : '');
        $cappedTooltipText = __('t_merchant.buy_capped_tooltip');
        // Displayed numerics: 0 across the board when fully disabled (mirrors live).
        $shownDaily = $fullyDisabled ? 0 : $dailyProduction;
        $shownCost = $fullyDisabled ? 0 : $costDm;
        ?>
        <div class="resource_box">
            <div class="sale_badge disabled"></div>
            <div class="resource_img resource_img_<?= e($resourceKey) ?>">
                <?php if ($isCapped && !$fullyDisabled): ?>
                    <div class="tooltip cappedToolTip"
                         aria-label="<?= e($cappedTooltipText) ?>"
                         data-tooltip-title="<?= e($cappedTooltipText) ?>"></div>
                <?php endif ?>
            </div>
            <div class="resource_name">
                <input type="text"
                       class="<?= $inputClasses ?>"
                       min="0"
                       max="<?= $shownDaily ?>"
                       aria-valuemin="0"
                       aria-valuemax="<?= $shownDaily ?>"
                       data-resource-type="<?= $resourceTypeId ?>"
                       data-original="<?= $shownDaily ?>"
                       data-original-price="<?= $shownCost ?>"
                       data-daily-production="<?= $shownDaily ?>"
                       value="<?= number_format($shownDaily, 0, ',', '.') ?>"
                       <?= $fullyDisabled ? 'disabled aria-disabled="true"' : '' ?>>
            </div>
        </div>
        <?php
    };
@endphp

@include('ingame.merchant._buy-resources-styles')

<div id="tabs-buyResource" class="big_tab_content ui-tabs-panel ui-corner-bottom ui-widget-content" aria-labelledby="ui-id-7" role="tabpanel" aria-hidden="false">

    <div class="teaser_txt">
        <h2>{{ __('t_merchant.buy_daily_production_title') }}</h2>
        <p>{{ __('t_merchant.buy_daily_production_desc') }}</p>
    </div>

    <div class="content_inner buy_resources productionBasedPackages" data-dark-matter="{{ $darkMatter }}">
        <div class="fill_resource">
            @foreach ($boxes as $box)
                <div class="{{ $box['box_classes'] }}">
                    @if ($box['package_key'] === 'allLocalResources')
                        {{-- "All resources" box: one .resource_box per sub-resource, in canonical order --}}
                        @foreach (['metal', 'crystal', 'deuterium'] as $subKey)
                            @php
                                $sub = $box['sub_packages'][$subKey] ?? [];
                                $subDaily = (int) ($sub['daily_production'] ?? 0);
                                $subCost = (int) ($sub['cost_dm'] ?? 0);
                                $subCapped = !empty($sub['is_capped']);
                                $subOfferable = (int) ($sub['amount'] ?? 0);
                                $renderResourceBox($subKey, $subDaily, $subCost, $subCapped, $subOfferable);
                            @endphp
                        @endforeach
                    @else
                        @php
                            $pkg = $buyPackages[$box['package_key'] === 'allLocalResources' ? 'all' : $box['resource_key']];
                            $renderResourceBox(
                                $box['resource_key'],
                                $box['daily_production'],
                                $box['cost_dm'],
                                $box['is_capped'] === '1',
                                (int) ($pkg['amount'] ?? 0)
                            );
                        @endphp
                    @endif

                    <p class="fillup_txt">
                        {{ __('t_merchant.buy_action') }} <br>
                        <span>{{ __('t_merchant.resource_' . $box['resource_key']) }}</span>
                    </p>
                    <p class="fillup_cost">
                        {{ __('t_merchant.cost_dm_label') }} <br>
                        <span class="premium_txt {{ $box['is_capped'] === '1' ? 'overmark' : '' }}">{{ number_format($box['cost_dm_display'], 0, ',', '.') }}</span> {{ __('t_merchant.dark_matter_short') }}
                    </p>
                    <div class="clearfix fill_resource_ctn">&nbsp;</div>
                    <div class="btn_wrap">
                        @php
                            $btnIsPremium = $box['premium_state'];
                            $btnClasses = $btnIsPremium ? 'btn_premium small js_buyResourceBtn' : 'btn_blue js_buyResourceBtn';
                            $btnLabel = $btnIsPremium ? __('t_merchant.buy_dark_matter') : __('t_merchant.refill_resources');
                        @endphp
                        <a role="button"
                           class="{{ $btnClasses }}"
                           data-itemuuid="{{ $box['item_uuid'] }}"
                           data-resource="{{ $box['package_key'] }}"
                           data-package-type="{{ $box['package_key'] }}"
                           data-premium-percent="100"
                           data-premium-costs="{{ $box['cost_dm'] }}"
                           data-premium-value="0"
                           data-min-premium-costs="{{ \OGame\Services\BuyResourcesService::MIN_COST_DM }}"
                           data-buy-button-class="fillup_100percent"
                           data-new-value-formatted="0"
                           data-trader-buy-resource="{{ $traderBuyToken }}"
                           data-is-capped="{{ $box['is_capped'] }}"
                           data-production-lowered="0"
                           data-sufficient-dark-matter="{{ $box['sufficient_dm'] }}"
                           aria-label="{{ $box['package_key'] }} {{ __('t_merchant.refill_resources') }}">{{ $btnLabel }}</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="clearfloat"></div>
        <div class="roundBox hints">
            <h2>{{ __('t_merchant.notices') }}</h2>
            <ul class="ListLinks">
                <li>{{ __('t_merchant.buy_notice_daily_production') }}</li>
                <li>{{ __('t_merchant.buy_notice_storage_capacity') }}</li>
            </ul>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function () {
        if (window.__buyResourcesBound) return;
        window.__buyResourcesBound = true;

        // Tab switching between #tabs-buyResource and #tabs-changeResource. Re-bound
        // every load to make sure the click handler attaches even after the partial
        // is rendered into the AJAX-swapped #contentWrapper.
        $(document).on('click', '.big_tabs .ui-tabs-nav a', function (e) {
            e.preventDefault();
            var $a = $(this);
            var target = $a.attr('href');
            $('.big_tabs .ui-tabs-nav li')
                .removeClass('ui-tabs-active ui-state-active')
                .addClass('ui-state-default')
                .attr({ 'aria-selected': 'false', 'aria-expanded': 'false', tabindex: '-1' });
            $a.closest('li')
                .addClass('ui-tabs-active ui-state-active')
                .removeClass('ui-state-default')
                .attr({ 'aria-selected': 'true', 'aria-expanded': 'true', tabindex: '0' });
            $('.big_tab_content').hide().attr('aria-hidden', 'true');
            $(target).show().attr('aria-hidden', 'false');
        });

        // Live recompute of cost when the user edits the amount input. Single-
        // resource boxes only — the "all" bundle remains fixed-amount because it
        // would require per-resource breakdown that isn't useful UX-wise.
        // Cost = max(MIN_COST, ceil(amount * coefficient)).
        var COEFS = { metal: 0.153856, crystal: 0.650939, deuterium: 2.477391 };
        var MIN_COST = {{ \OGame\Services\BuyResourcesService::MIN_COST_DM }};

        function parseInt0(s) {
            var n = parseInt(String(s).replace(/[^\d-]/g, ''), 10);
            return isFinite(n) ? n : 0;
        }
        function formatTh(n) { return Number(n).toLocaleString('it-IT'); }

        function recomputeBox($box) {
            var $btn = $box.find('a.js_buyResourceBtn');
            var pkg = $btn.attr('data-package-type');
            if (pkg === 'allLocalResources') return;  // no live edit on bundle

            var $input = $box.find('input').first();
            var daily = parseInt0($input.attr('data-daily-production'));
            var origPrice = parseInt0($input.attr('data-original-price'));
            var coef = COEFS[pkg];
            if (!coef || daily <= 0) return;

            var requested = parseInt0($input.val());
            if (requested < 0) requested = 0;
            if (requested > daily) requested = daily;
            $input.val(formatTh(requested));

            var cost = requested > 0 ? Math.max(MIN_COST, Math.ceil(requested * coef)) : 0;
            var percent = Math.round((requested / daily) * 100);
            var dm = parseInt0($('.content_inner.buy_resources').attr('data-dark-matter'));
            var sufficient = dm >= cost;

            $btn.attr({
                'data-premium-costs': cost,
                'data-premium-value': requested,
                'data-premium-percent': percent,
                'data-new-value-formatted': requested,
                'data-sufficient-dark-matter': sufficient ? '1' : '0'
            });

            // Premium / upsell state toggling — mirrors the server-side Blade logic:
            //   * sufficient   → btn_blue "Riempire risorse?", no .premium on box,
            //                    cost text = real cost (overmark if capped)
            //   * !sufficient  → btn_premium small "Compra adesso", .premium class
            //                    on box, cost text = MIN_COST (500) without overmark
            var enteringPremium = !sufficient && requested > 0;
            if (enteringPremium) {
                if (!$btn.hasClass('btn_premium')) {
                    $btn.removeClass('btn_blue').addClass('btn_premium small')
                        .text(@json(__('t_merchant.buy_dark_matter')));
                }
                $box.addClass('premium');
                $box.find('.fillup_cost .premium_txt').removeClass('overmark').text(formatTh(MIN_COST));
            } else {
                if ($btn.hasClass('btn_premium')) {
                    $btn.removeClass('btn_premium small').addClass('btn_blue')
                        .text(@json(__('t_merchant.refill_resources')));
                }
                $box.removeClass('premium');
                $box.find('.fillup_cost .premium_txt').text(formatTh(cost));
            }
            $box.toggleClass('disabled', requested <= 0 || !sufficient);
        }

        // Lock the input when the box has zero daily production: there is nothing
        // to size, so leaving it editable would just confuse the user. The "all"
        // bundle and the deuterium-of-no-fusion case both fall here.
        $('#tabs-buyResource .resource_name input').each(function () {
            var $i = $(this);
            var daily = parseInt0($i.attr('data-daily-production'));
            if (daily <= 0) $i.prop('readonly', true);
        });

        $(document).on('input change', '#tabs-buyResource .resource_name input', function () {
            recomputeBox($(this).closest('.roundBox.fillup'));
        });

        // Helper: morph a btn_blue button into the "Compra adesso" upsell CTA.
        // Idempotent — calling it on an already-morphed button is a no-op. Also
        // applies the `.premium` class to the box and overrides the fillup_cost
        // span to MIN_COST without overmark, matching OGame ufficiale.
        function morphToUpsell($btn) {
            var $box = $btn.closest('.roundBox.fillup');
            if (!$btn.hasClass('btn_premium') || !$btn.hasClass('small')) {
                $btn.removeClass('btn_blue').addClass('btn_premium small')
                    .text(@json(__('t_merchant.buy_dark_matter')));
            }
            $box.addClass('premium');
            $box.find('.fillup_cost .premium_txt').removeClass('overmark').text(MIN_COST.toLocaleString('it-IT'));
        }

        // OGame ufficiale upsell flow: when DM is insufficient, the first click on
        // the "Riempire risorse?" button (btn_blue) morphs it into a "Acquista la
        // Materia Oscura" CTA (btn_premium small). A second click then navigates to
        // the premium shop with showDarkMatter=1. Mirrors the live behaviour.
        var BUY_DM_URL = @json(route('premium.index', ['showDarkMatter' => 1]));

        $(document).on('click', '.js_buyResourceBtn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $box = $btn.closest('.roundBox.fillup');

            // OGame ufficiale: the .disabled class on the box is visual styling
            // (greyed-out look when DM is insufficient or storage is capped) but the
            // click is NOT blocked. We only short-circuit when there is genuinely
            // nothing to deliver. For the "all" bundle, that means ALL three
            // sub-resources have daily=0 — if at least one sub-resource is buyable
            // (e.g. metal is full but crystal has headroom), the click must proceed.
            var pkgType = $btn.attr('data-package-type');
            var dailyProd = 0;
            if (pkgType === 'allLocalResources') {
                $box.find('input').each(function () {
                    dailyProd += parseInt0($(this).attr('data-daily-production'));
                });
            } else {
                dailyProd = parseInt0($box.find('input').first().attr('data-daily-production'));
            }
            if (dailyProd <= 0) return;

            // Already morphed into the "Acquista MO" CTA? Second click → shop.
            if ($btn.hasClass('btn_premium') && $btn.hasClass('small')) {
                window.location.href = BUY_DM_URL;
                return;
            }

            // First click with insufficient DM → morph into upsell CTA, no purchase.
            if ($btn.attr('data-sufficient-dark-matter') !== '1') {
                morphToUpsell($btn);
                return;
            }

            var packageType = $btn.attr('data-package-type');
            var data = { package: packageType, _token: '{{ csrf_token() }}' };

            // For single-resource packages, send the user-edited amount so the
            // server can charge for a partial daily production.
            if (packageType !== 'allLocalResources') {
                var $input = $box.find('input').first();
                var requested = parseInt0($input.val());
                var daily = parseInt0($input.attr('data-daily-production'));
                if (requested > 0 && requested !== daily) {
                    data.amount = requested;
                }
            }

            $btn.addClass('disabled');

            $.ajax({
                url: '{{ route('merchant.buy-resources') }}',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    // Always re-enable the button: if anything below throws (missing
                    // helper, partial swap fails, etc.) we must not leave a frozen UI.
                    $btn.removeClass('disabled');

                    if (response.success) {
                        // Patch the top resource bar so the user immediately sees the
                        // package delivery / DM debit. window.reloadResources here is
                        // currently a no-op (it needs a payload from getAjaxResourcebox,
                        // which is commented out as TODO in resources/js/ingame/...js).
                        // ResourceTicker (window.resourcesBar) keeps its own internal
                        // `resources` state and rewrites the DOM each tick from there —
                        // patching DOM text directly gets overwritten on the next tick.
                        // So we mutate resourcesBar.resources[*].amount instead.
                        try {
                            var credited = response.credited || {};
                            var cost = parseInt(response.cost_dm || 0, 10) || 0;
                            var rb = window.resourcesBar;
                            if (rb && rb.resources) {
                                if (rb.resources.metal)      rb.resources.metal.amount     += parseInt(credited.metal || 0, 10) || 0;
                                if (rb.resources.crystal)    rb.resources.crystal.amount   += parseInt(credited.crystal || 0, 10) || 0;
                                if (rb.resources.deuterium)  rb.resources.deuterium.amount += parseInt(credited.deuterium || 0, 10) || 0;
                                if (rb.resources.darkmatter) rb.resources.darkmatter.amount -= cost;
                                // Force one update tick so the user sees the new values
                                // immediately (the ticker would do it within a fraction
                                // of a second anyway, but lag is perceptible).
                                if (typeof rb.update === 'function') {
                                    try { rb.update(); } catch (_) {}
                                }
                            }

                            if (typeof messageBoxNotify === 'function') {
                                messageBoxNotify(LocalizationStrings.success, response.message);
                            }
                        } catch (_) { /* best-effort UI updates */ }

                        // Refresh the resource-market panel so the box reflects the
                        // new planet state (DM debited, storage refilled). Failures
                        // here are non-fatal — the buy already completed server-side.
                        var ajaxUrl = '{{ route('merchant.resource-market.partial') }}';
                        $.get(ajaxUrl).done(function (html) {
                            var wrapper = document.getElementById('contentWrapper');
                            if (!wrapper) return;
                            wrapper.innerHTML = html;
                            wrapper.querySelectorAll('script').forEach(function (old) {
                                var s = document.createElement('script');
                                for (var i = 0; i < old.attributes.length; i++) {
                                    s.setAttribute(old.attributes[i].name, old.attributes[i].value);
                                }
                                s.textContent = old.textContent;
                                old.replaceWith(s);
                            });
                        });
                    } else {
                        if (typeof errorBoxNotify === 'function') {
                            errorBoxNotify(LocalizationStrings.error,
                                response.message || @json(__('t_merchant.error.buy.execution_failed', ['error' => ''])));
                        }
                    }
                },
                error: function (xhr) {
                    var resp = xhr.responseJSON || {};
                    // Server detected insufficient DM (e.g., DM dropped between the
                    // page render and the click). Trigger the upsell morph instead
                    // of showing a toast — matches OGame ufficiale behaviour.
                    if (resp.code === 'insufficient_dark_matter') {
                        $btn.attr('data-sufficient-dark-matter', '0').removeClass('disabled');
                        morphToUpsell($btn);
                        return;
                    }
                    var msg = resp.message ||
                              @json(__('t_merchant.error.buy.execution_failed', ['error' => '']));
                    if (typeof errorBoxNotify === 'function') {
                        errorBoxNotify(LocalizationStrings.error, msg);
                    }
                    $btn.removeClass('disabled');
                }
            });
        });
    })();
</script>
