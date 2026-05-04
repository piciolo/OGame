@extends('ingame.layouts.main')

@section('content')
@php
    /** @var \OGame\Models\ImportExportOffer $offer */
    /** @var \OGame\Models\ImportExportItem  $item */
    /** @var \OGame\Models\Planet            $currentPlanet */
    /** @var array{metal:int,crystal:int,deuterium:int,honor:int} $maxInputs */
    /** @var int $honorPoints */
    /** @var \Illuminate\Support\Collection $planets */
    /** @var \Illuminate\Support\Collection $moons */

    $isConsumed   = in_array($offer->status, ['paid', 'taken_dm']);
    $changeCost   = (int) $item->change_dm_cost;
    $takeCost     = \OGame\Services\ImportExportService::TAKE_ITEM_DM_COST;
    $changesLeft  = max(0, \OGame\Services\ImportExportService::MAX_CHANGES_PER_CYCLE - (int) $offer->change_count);
    $showHonorRow = $honorPoints > 0;
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
    #div_traderImportExport .togglePanel {
        position:absolute; background:#0a2240; border:1px solid #2a5570;
        padding:4px; min-width:180px; z-index:1000; display:none;
    }
    #div_traderImportExport .togglePanel.open { display:block; }
    #div_traderImportExport .togglePanel ul { list-style:none; padding:0; margin:0; display:none; }
    #div_traderImportExport .togglePanel ul.active { display:block; }
    #div_traderImportExport .togglePanel li { padding:3px 6px; cursor:pointer; }
    #div_traderImportExport .togglePanel li:hover { background:#1a3550; }
    #div_traderImportExport .source { display:inline-block; cursor:pointer; opacity:0.5; }
    #div_traderImportExport .source.selected { opacity:1; }
    #div_traderImportExport .bargain_overlay { display:none; }
    #div_traderImportExport .bargain_overlay.visible { display:block; }
    #div_traderImportExport .bargain_left_overlay { display:none; }
    #div_traderImportExport .bargain_left_overlay.visible { display:block; }
</style>

<div id="traderOverviewcomponent" class="maincontent">
<div id="traderOverview">
<div id="inhalt">
    <div id="planet" style="background-position: 0px -220px; height: 250px;" class="detail">
        <div id="detail" class="detail_screen small">
            <div id="techDetailLoading"></div>
        </div>
        <div id="header_text" style="display: block;">
            <h2>{{ __('t_ingame.import_export.title') }}</h2>
            <a class="back_to_overview js_backToOverview tooltip js_hideTipOnMobile right" href="{{ route('merchant.index') }}" style="display: inline;"></a>
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
                                        <a class="tooltip source planet js_planet {{ !$isMoon ? 'selected' : '' }}" data-source-type="planet" title="{{ __('t_ingame.import_export.title') }}"></a>
                                        <a class="tooltip source moon js_moon {{ $isMoon ? 'selected' : '' }}" data-source-type="moon"></a>
                                        <a class="tooltip source star js_star" data-source-type="star"></a>

                                        <a id="js_toggleLinkImportExport" class="js_valSourcePlanet toggleHidden toggleLink">
                                            <img src="{{ asset('img/planets/small/' . ($currentPlanet->planet_type ?? 1) . '.gif') }}" alt="" onerror="this.style.display='none'">
                                            <span class="option_source">{{ Str::limit($currentPlanet->name, 8, '...') }} [{{ $currentPlanet->galaxy }}:{{ $currentPlanet->system }}:{{ $currentPlanet->planet }}]</span>
                                        </a>

                                        <div id="js_togglePanelImportExport" class="togglePanel">
                                            <ul class="planet {{ !$isMoon ? 'active' : '' }}">
                                                @foreach($planets as $p)
                                                    <li data-planet-id="{{ $p->id }}" class="{{ $p->id === $currentPlanet->id ? 'selected' : '' }}">
                                                        <span class="option_source">{{ $p->name }} [{{ $p->galaxy }}:{{ $p->system }}:{{ $p->planet }}]</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <ul class="moon {{ $isMoon ? 'active' : '' }}">
                                                @foreach($moons as $m)
                                                    <li data-planet-id="{{ $m->id }}" class="{{ $m->id === $currentPlanet->id ? 'selected' : '' }}">
                                                        <span class="option_source">{{ $m->name }} [{{ $m->galaxy }}:{{ $m->system }}:{{ $m->planet }}]</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>

                                    <table class="table_ressources">
                                        <tbody>
                                            @foreach([
                                                ['key' => 'metal',     'mult' => '1',   'css' => 'metal',     'class' => 'normalResource'],
                                                ['key' => 'crystal',   'mult' => '1.5', 'css' => 'crystal',   'class' => 'normalResource'],
                                                ['key' => 'deuterium', 'mult' => '3',   'css' => 'deuterium', 'class' => 'normalResource'],
                                            ] as $row)
                                                <tr class="{{ $row['class'] }}">
                                                    <td><div class="resourceIcon {{ $row['css'] }} resource_label"></div></td>
                                                    <td class="multiplier undermark tooltip"><span class="dark_highlight_tablet">x {{ $row['mult'] }}</span></td>
                                                    <td><input type="text" name="{{ $row['key'] }}" value="0" data-mult="{{ $row['mult'] }}" data-max="{{ $maxInputs[$row['key']] }}" class="ie_input"></td>
                                                    <td><a class="value-control more js_valButton ie_btn_inc" data-target="{{ $row['key'] }}">+</a></td>
                                                    <td><a class="value-control max js_valButton ie_btn_max" data-target="{{ $row['key'] }}">&gt;&gt;</a></td>
                                                </tr>
                                                <tr class="{{ $row['class'] }}">
                                                    <td></td>
                                                    <td>
                                                        <div class="max_hint">({{ __('t_ingame.import_export.max_hint_prefix') }}
                                                            <span class="max_planet_res max_planet_{{ $row['key'] }}">{{ number_format($maxInputs[$row['key']], 0, ',', '.') }}</span>)
                                                        </div>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            @endforeach

                                            @if($showHonorRow)
                                                {{-- Riga honor: condizionale, solo se honor_points > 0 (logica OGame) --}}
                                                <tr class="honorResource">
                                                    <td><img class="resource_label" alt="honor" src="{{ asset('img/icons/honor.gif') }}" onerror="this.style.display='none'"></td>
                                                    <td class="multiplier undermark tooltip"><span class="dark_highlight_tablet">x 100</span></td>
                                                    <td><input type="text" name="honor" value="0" data-mult="100" data-max="{{ $maxInputs['honor'] }}" class="ie_input"></td>
                                                    <td><a class="ie_btn_inc" data-target="honor">+</a></td>
                                                    <td><a class="ie_btn_max" data-target="honor">&gt;&gt;</a></td>
                                                </tr>
                                                <tr class="honorResource">
                                                    <td></td>
                                                    <td>
                                                        <div class="max_hint">({{ __('t_ingame.import_export.max_hint_prefix') }}
                                                            <span class="max_planet_res max_planet_honor">{{ number_format($maxInputs['honor'], 0, ',', '.') }}</span>)
                                                        </div>
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            @endif
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

    if (totalEl && payBtn) {
        function recalc() {
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

    // Planet/Moon source selector
    var togglePanel = document.getElementById('js_togglePanelImportExport');
    var toggleLink  = document.getElementById('js_toggleLinkImportExport');
    if (toggleLink && togglePanel) {
        toggleLink.addEventListener('click', function () {
            togglePanel.classList.toggle('open');
        });
        document.querySelectorAll('#js_togglePanelImportExport li').forEach(function (li) {
            li.addEventListener('click', function () {
                var pid = li.getAttribute('data-planet-id');
                window.location.href = '{{ route('importexport.index') }}?planet_id=' + pid;
            });
        });
    }
    document.querySelectorAll('.selectWrapper .source').forEach(function (a) {
        a.addEventListener('click', function () {
            var type = a.getAttribute('data-source-type');
            // Toggle UL active visibility
            document.querySelectorAll('#js_togglePanelImportExport > ul').forEach(function (ul) {
                ul.classList.remove('active');
            });
            var target = document.querySelector('#js_togglePanelImportExport > ul.' + type);
            if (target) target.classList.add('active');
            document.querySelectorAll('.selectWrapper .source').forEach(function (s) { s.classList.remove('selected'); });
            a.classList.add('selected');
            if (togglePanel) togglePanel.classList.add('open');
        });
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
