@extends('ingame.layouts.main')

@section('content')
@php
    /** @var \OGame\Models\ImportExportOffer $offer */
    /** @var \OGame\Models\ImportExportItem  $item */
    /** @var \OGame\Models\Planet            $currentPlanet */
    /** @var array{metal:int,crystal:int,deuterium:int,honor:int} $maxInputs */
    /** @var int $honorPoints */
    /** @var array<int,array{id:int,name:string,galaxy:int,system:int,planet:int,metal:int,crystal:int,deuterium:int,icon:string}> $planets */
    /** @var array<int,array{id:int,name:string,galaxy:int,system:int,planet:int,metal:int,crystal:int,deuterium:int,icon:string}> $moons */
    /** @var string $currentIcon */

    $isConsumed   = in_array($offer->status, ['paid', 'taken_dm']);
    $changeCost   = (int) $item->change_dm_cost;
    $takeCost     = \OGame\Services\ImportExportService::TAKE_ITEM_DM_COST;
    $changesLeft  = max(0, \OGame\Services\ImportExportService::MAX_CHANGES_PER_CYCLE - (int) $offer->change_count);
    $isMoon       = (int) $currentPlanet->planet_type === 3;
@endphp

<style>
    /* Stile bottoni OGame: rimuovo aspetto HTML default. */
    #div_traderImportExport a.pay,
    #div_traderImportExport a.bargain {
        cursor: pointer; user-select: none;
    }
    #div_traderImportExport a.pay.disabled,
    #div_traderImportExport a.bargain.disabled {
        cursor: not-allowed; opacity: 0.5;
    }
    /* + e >> usano direttamente sprite OGame via classi value-control more/max */
    /* togglePanel/source: stile completo gestito da OGame CSS originale
       (#traderOverview .source, #traderOverview .togglePanel ecc.) */
    #div_traderImportExport .bargain_overlay { display:none; }
    #div_traderImportExport .bargain_overlay.visible { display:block; }
    #div_traderImportExport .bargain_left_overlay { display:none; }
    #div_traderImportExport .bargain_left_overlay.visible { display:block; }
</style>

<div id="traderOverviewcomponent" class="maincontent">
<div id="traderOverview">
<div id="inhalt">
    <div id="planet" style="background-position: -546px -220px; height: 250px;" class="detail">
        <div id="detail" class="detail_screen small">
            <div id="techDetailLoading"></div>
        </div>
        <div id="header_text" style="display: block;">
            <h2>{{ __('t_ingame.import_export.title') }}</h2>
            <a class="back_to_overview js_backToOverview tooltip js_hideTipOnMobile left" href="{{ route('merchant.index') }}" style="display: inline;"></a>
            <a class="small_back_to_overview js_backToOverview tooltip js_hideTipOnMobile" href="{{ route('merchant.index') }}"></a>
        </div>
    </div>
    <div class="c-left c-small"></div>
    <div class="c-right c-small"></div>

    <div id="div_traderImportExport" class="div_trader">
        <div class="header"><h2>{{ __('t_ingame.import_export.title') }}</h2></div>

        <div class="content">
            <p class="stimulus">{{ __('t_ingame.import_export.stimulus') }}</p>

            @if(session('error'))
                <div class="error_box" style="color:#ff5555;text-align:center;margin:8px 0;">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="success_box" style="color:#9eff7a;text-align:center;margin:8px 0;">{{ session('success') }}</div>
            @endif

            {{-- LEFT: Offerta del giorno --}}
            <div class="left_box">
                <div class="left_header"><h2>{{ __('t_ingame.import_export.offer_of_the_day') }}</h2></div>
                <div class="left_content">
                    <div class="bargain_left_overlay {{ $isConsumed ? 'visible' : '' }}"></div>
                    <div class="image_140px">
                        <img src="{{ asset($item->icon_path) }}" alt="" onerror="this.style.opacity=0.3" style="width:140px;height:140px;">
                        <a class="detail_button tooltip r_common_140px"
                           title="{{ $item->name }}::{{ $item->description }}">
                            <span class="ecke">
                                <span class="level amount">?</span>
                            </span>
                        </a>
                    </div>

                    <label class="import_price">{{ __('t_ingame.import_export.price_label') }}</label>
                    <div class="price js_import_price">{{ number_format((int) $offer->price, 0, ',', '.') }}</div>

                    <label class="import_total">{{ __('t_ingame.import_export.total_label') }}</label>
                    <div class="total js_import_total">0</div>

                    <br class="clearfloat">
                </div>
                <div class="left_footer"></div>
            </div>

            {{-- RIGHT: Commercia --}}
            <div class="right_box">
                <div class="right_header"><h2>{{ __('t_ingame.import_export.trade_panel') }}</h2></div>
                <div class="right_content">

                    {{-- Overlay post-acquisto (OGame live: bargain_overlay display:none di default) --}}
                    @if($isConsumed)
                        <div class="bargain_overlay visible">
                            <p class="got_item_text">{{ __('t_ingame.import_export.bought_1_item', ['name' => $item->name]) }}</p>
                            <p class="bargain_text">{{ __('t_ingame.import_export.no_more_today') }}</p>
                        </div>
                    @endif

                    @if(!$isConsumed)
                        <form method="POST" action="{{ route('importexport.pay') }}" id="ie_form_pay">
                            @csrf
                            <input type="hidden" name="planet_id" value="{{ $currentPlanet->id }}" id="ie_source_planet_id">
                            <div class="payment">
                                <div class="resourceSelection">
                                    <div class="selectWrapper">
                                        <a href="javascript:void(0);" class="tooltip js_hideTipOnMobile source planet js_planet {{ !$isMoon ? 'selected' : '' }}" data-tooltip-title="{{ __('t_ingame.import_export.source_planet_tooltip') }}"></a>
                                        <a href="javascript:void(0);" class="tooltip js_hideTipOnMobile source moon js_moon {{ $isMoon ? 'selected' : '' }}" data-tooltip-title="{{ __('t_ingame.import_export.source_moon_tooltip') }}"></a>
                                        <a href="javascript:void(0);" class="tooltip js_hideTipOnMobile source honor js_honor" data-tooltip-title="{{ __('t_ingame.import_export.source_honor_tooltip') }}"></a>
                                        <a id="js_toggleLinkImportExport" class="js_valSourcePlanet toggleHidden toggleLink" href="#togglePanel" title="{{ __('t_ingame.import_export.title') }}">
                                            <img alt="{{ $currentPlanet->name }}" src="{{ $currentIcon }}" width="18" height="18">
                                            <span class="option_source" title="{{ $currentPlanet->name }}">{{ $currentPlanet->name }} [{{ $currentPlanet->galaxy }}:{{ $currentPlanet->system }}:{{ $currentPlanet->planet }}]</span>
                                        </a>
                                    </div>
                                    <div id="js_togglePanelImportExport" class="togglePanel" style="display:none">
                                        <ul class="planet active">
                                            @foreach($planets as $p)
                                                <li id="{{ $p['id'] }}"
                                                    data-planet-id="{{ $p['id'] }}"
                                                    data-metal="{{ $p['metal'] }}"
                                                    data-crystal="{{ $p['crystal'] }}"
                                                    data-deuterium="{{ $p['deuterium'] }}"
                                                    class="dark_highlight_tablet planet_select_item @if($p['id'] === $currentPlanet->id) selected active @endif">
                                                    <img src="{{ $p['icon'] }}" width="12" height="12" alt="{{ $p['name'] }}">
                                                    <span class="option_source">{{ $p['name'] }} [{{ $p['galaxy'] }}:{{ $p['system'] }}:{{ $p['planet'] }}]</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if(!empty($moons))
                                            <ul class="moon active" style="display:none">
                                                @foreach($moons as $m)
                                                    <li id="{{ $m['id'] }}"
                                                        data-planet-id="{{ $m['id'] }}"
                                                        data-metal="{{ $m['metal'] }}"
                                                        data-crystal="{{ $m['crystal'] }}"
                                                        data-deuterium="{{ $m['deuterium'] }}"
                                                        class="dark_highlight_tablet planet_select_item @if($m['id'] === $currentPlanet->id) selected active @endif">
                                                        <img src="{{ $m['icon'] }}" width="12" height="12" alt="{{ $m['name'] }}">
                                                        <span class="option_source">{{ $m['name'] }} [{{ $m['galaxy'] }}:{{ $m['system'] }}:{{ $m['planet'] }}]</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    <table class="table_ressources">
                                        <tbody>
                                            @foreach([
                                                ['key' => 'metal',     'mult' => '1',   'css' => 'metal',     'class' => 'normalResource'],
                                                ['key' => 'crystal',   'mult' => '1.5', 'css' => 'crystal',   'class' => 'normalResource'],
                                                ['key' => 'deuterium', 'mult' => '3',   'css' => 'deuterium', 'class' => 'normalResource'],
                                            ] as $row)
                                                <tr class="{{ $row['class'] }}" data-row-type="normal">
                                                    <td><div class="resourceIcon {{ $row['css'] }} resource_label"></div></td>
                                                    <td class="multiplier undermark tooltip"><span class="dark_highlight_tablet">x {{ $row['mult'] }}</span></td>
                                                    <td><input type="text" name="{{ $row['key'] }}" value="0" data-mult="{{ $row['mult'] }}" data-max="{{ $maxInputs[$row['key']] }}" class="ie_input"></td>
                                                    <td><a class="value-control more js_valButton ie_btn_inc" data-target="{{ $row['key'] }}">+</a></td>
                                                    <td><a class="value-control max js_valButton ie_btn_max" data-target="{{ $row['key'] }}">&gt;&gt;</a></td>
                                                </tr>
                                                <tr class="{{ $row['class'] }}" data-row-type="normal">
                                                    <td></td>
                                                    <td>
                                                        <div class="max_hint">({{ __('t_ingame.import_export.max_hint_prefix') }}
                                                            <span class="max_planet_res max_planet_{{ $row['key'] }}">{{ number_format($maxInputs[$row['key']], 0, ',', '.') }}</span>)
                                                        </div>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            @endforeach

                                            {{-- Riga honor: sempre presente nel DOM, visibile solo quando .js_honor selected.
                                                 Pattern AuctioneerController: data-row-type='honor' + display:none default. --}}
                                            <tr class="honorResource" data-row-type="honor" style="display:none">
                                                <td><div class="resourceIcon honor resource_label"></div></td>
                                                <td class="multiplier undermark tooltip"><span class="dark_highlight_tablet">x 100</span></td>
                                                <td><input type="text" name="honor" value="0" data-mult="100" data-max="{{ $maxInputs['honor'] }}" class="ie_input"></td>
                                                <td><a class="value-control more js_valButton ie_btn_inc" data-target="honor">+</a></td>
                                                <td><a class="value-control max js_valButton ie_btn_max" data-target="honor">&gt;&gt;</a></td>
                                            </tr>
                                            <tr class="honorResource" data-row-type="honor" style="display:none">
                                                <td></td>
                                                <td>
                                                    <div class="max_hint">({{ __('t_ingame.import_export.max_hint_prefix') }}
                                                        <span class="max_planet_res max_planet_honor">{{ number_format($maxInputs['honor'], 0, ',', '.') }}</span>)
                                                    </div>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <a class="pay disabled" id="ie_pay_btn">{{ __('t_ingame.import_export.pay_button') }}</a>
                            </div>
                        </form>
                    @endif

                </div>
                <div class="right_footer"></div>
            </div>
        </div>
        <div class="footer"></div>
    </div>
</div>
</div>
</div>

<script>
(function () {
    var price   = {{ (int) $offer->price }};
    var inputs  = document.querySelectorAll('.ie_input');
    var totalEl = document.querySelector('.js_import_total');
    var payBtn  = document.getElementById('ie_pay_btn');

    function recalc() {
        if (!totalEl || !payBtn) return;
        var total = 0;
        inputs.forEach(function (el) {
            var v = parseInt(el.value, 10) || 0;
            total += v * parseFloat(el.getAttribute('data-mult'));
        });
        total = Math.round(total);
        totalEl.textContent = total.toLocaleString('it-IT');
        if (total === price) payBtn.classList.remove('disabled');
        else                 payBtn.classList.add('disabled');
    }
    if (totalEl && payBtn) {
        inputs.forEach(function (el) { el.addEventListener('input', recalc); });

        document.querySelectorAll('.ie_btn_max').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-target');
                inputs.forEach(function (el) {
                    if (el.getAttribute('name') === key) {
                        var max  = parseInt(el.getAttribute('data-max'), 10) || 0;
                        var mult = parseFloat(el.getAttribute('data-mult'));
                        el.value = Math.min(max, Math.floor(price / mult));
                    } else el.value = 0;
                });
                recalc();
            });
        });

        document.querySelectorAll('.ie_btn_inc').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key     = btn.getAttribute('data-target');
                var current = 0;
                inputs.forEach(function (el) {
                    current += (parseInt(el.value, 10) || 0) * parseFloat(el.getAttribute('data-mult'));
                });
                var remaining = price - Math.round(current);
                if (remaining <= 0) return;
                inputs.forEach(function (el) {
                    if (el.getAttribute('name') !== key) return;
                    var mult = parseFloat(el.getAttribute('data-mult'));
                    var max  = parseInt(el.getAttribute('data-max'), 10) || 0;
                    var add  = Math.min(max - (parseInt(el.value, 10) || 0), Math.floor(remaining / mult));
                    if (add > 0) el.value = (parseInt(el.value, 10) || 0) + add;
                });
                recalc();
            });
        });

        payBtn.addEventListener('click', function () {
            if (payBtn.classList.contains('disabled')) return;
            document.getElementById('ie_form_pay').submit();
        });

        recalc();
    }

    // Planet/Moon source selector — pattern AuctioneerController (no reload).
    // Tutto via JS: il <li> ha data-metal/crystal/deuterium, click cambia source,
    // aggiorna max-hints + max input data-attr + ricalcola Totale, no roundtrip.
    var $    = function (s) { return document.querySelector(s); };
    var $$   = function (s) { return Array.prototype.slice.call(document.querySelectorAll(s)); };
    var price_ = {{ (int) $offer->price }};
    var sourcePlanetInput = $('#ie_source_planet_id');
    var togglePanel       = $('#js_togglePanelImportExport');
    var toggleLink        = $('#js_toggleLinkImportExport');

    function updateMaxHints(p) {
        ['metal','crystal','deuterium'].forEach(function (r) {
            var span = $('.max_planet_' + r);
            var input = $('.ie_input[name="' + r + '"]');
            var mult  = r === 'metal' ? 1 : r === 'crystal' ? 1.5 : 3;
            // Cap a min(risorsa disponibile, prezzo/moltiplicatore) — pattern OGame
            var maxRow = Math.min(p[r] || 0, Math.floor(price_ / mult));
            if (span)  span.textContent  = maxRow.toLocaleString('it-IT');
            if (input) input.setAttribute('data-max', maxRow);
        });
    }
    function currentLi() {
        var pid = sourcePlanetInput ? sourcePlanetInput.value : '';
        return $('#js_togglePanelImportExport li[data-planet-id="' + pid + '"]');
    }
    function selectLi(li) {
        if (!li) return;
        sourcePlanetInput.value = li.dataset.planetId;
        $$('#js_togglePanelImportExport li').forEach(function (x) { x.classList.remove('active'); x.classList.remove('selected'); });
        li.classList.add('active'); li.classList.add('selected');
        var linkSpan = $('#js_toggleLinkImportExport .option_source');
        var linkImg  = $('#js_toggleLinkImportExport img');
        var srcSpan  = li.querySelector('.option_source');
        var srcImg   = li.querySelector('img');
        if (linkSpan && srcSpan) linkSpan.textContent = srcSpan.textContent;
        if (linkImg  && srcImg)  linkImg.src = srcImg.src;
        updateMaxHints({metal:+li.dataset.metal, crystal:+li.dataset.crystal, deuterium:+li.dataset.deuterium});
        $$('.ie_input').forEach(function (el) { el.value = '0'; });
        if (togglePanel) togglePanel.style.display = 'none';
        if (typeof recalc === 'function') recalc();
    }
    function setSource(kind) {
        if (kind === 'moon' && $$('#js_togglePanelImportExport ul.moon li').length === 0) return;
        $$('.selectWrapper .source').forEach(function (s) { s.classList.remove('selected'); });
        var src = $('.selectWrapper .source.' + kind);
        if (src) src.classList.add('selected');

        var isHonor = kind === 'honor';
        // Toggle righe normal vs honor (pattern AuctioneerController)
        $$('tr[data-row-type=normal]').forEach(function (tr) { tr.style.display = isHonor ? 'none' : ''; });
        $$('tr[data-row-type=honor]').forEach(function (tr) { tr.style.display = isHonor ? '' : 'none'; });
        // Quando honor: nascondi toggleLink (no corpo da scegliere) + chiudi panel
        if (toggleLink) toggleLink.style.visibility = isHonor ? 'hidden' : '';
        if (togglePanel) togglePanel.style.display = 'none';
        // Reset valori input non pertinenti alla sorgente attiva
        if (isHonor) {
            ['metal','crystal','deuterium'].forEach(function (r) {
                var i = $('.ie_input[name="' + r + '"]'); if (i) i.value = '0';
            });
        } else {
            var hi = $('.ie_input[name="honor"]'); if (hi) hi.value = '0';
        }

        if (kind === 'moon' || kind === 'planet') {
            var planetsUl = $('#js_togglePanelImportExport ul.planet');
            var moonsUl   = $('#js_togglePanelImportExport ul.moon');
            if (planetsUl) planetsUl.style.display = kind === 'moon' ? 'none' : '';
            if (moonsUl)   moonsUl.style.display   = kind === 'moon' ? '' : 'none';
            var activeUlSel = kind === 'moon' ? 'ul.moon' : 'ul.planet';
            var items = $$('#js_togglePanelImportExport ' + activeUlSel + ' li');
            var currentInList = items.find(function (li) { return li.dataset.planetId === sourcePlanetInput.value; });
            var target = currentInList || items[0];
            if (target) selectLi(target);
        }
        recalc();
    }
    if ($('.js_planet')) $('.js_planet').addEventListener('click', function (e) { e.preventDefault(); setSource('planet'); });
    if ($('.js_moon'))   $('.js_moon').addEventListener('click',   function (e) { e.preventDefault(); setSource('moon');   });
    if ($('.js_honor'))  $('.js_honor').addEventListener('click',  function (e) { e.preventDefault(); setSource('honor');  });
    if (toggleLink) toggleLink.addEventListener('click', function (e) {
        e.preventDefault();
        if (togglePanel) togglePanel.style.display = togglePanel.style.display === 'none' ? 'block' : 'none';
    });
    $$('#js_togglePanelImportExport li').forEach(function (li) {
        li.addEventListener('click', function () { selectLi(li); });
    });

    // Cambia / Prendi item
    var changeBtn = document.getElementById('ie_change_btn');
    var takeBtn   = document.getElementById('ie_take_btn');
    function postTo(url) {
        var f = document.createElement('form');
        f.method = 'POST'; f.action = url;
        var t = document.createElement('input');
        t.type = 'hidden'; t.name = '_token'; t.value = '{{ csrf_token() }}';
        f.appendChild(t);
        document.body.appendChild(f);
        f.submit();
    }
    if (changeBtn) changeBtn.addEventListener('click', function () {
        if (changeBtn.classList.contains('disabled')) return;
        postTo('{{ route('importexport.change') }}');
    });
    if (takeBtn) takeBtn.addEventListener('click', function () {
        postTo('{{ route('importexport.take') }}');
    });
})();
</script>
@endsection
