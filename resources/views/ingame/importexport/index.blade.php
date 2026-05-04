@extends('ingame.layouts.main')

@section('content')
@php
    /** @var \OGame\Models\ImportExportOffer $offer */
    /** @var \OGame\Models\ImportExportItem  $item */
    /** @var \OGame\Models\Planet            $currentPlanet */
    /** @var array{metal:int,crystal:int,deuterium:int,honor:int} $maxInputs */

    $isConsumed = in_array($offer->status, ['paid', 'taken_dm']);
    $changeCost = (int) $item->change_dm_cost;
    $takeCost   = \OGame\Services\ImportExportService::TAKE_ITEM_DM_COST;
    $changesLeft = max(0, \OGame\Services\ImportExportService::MAX_CHANGES_PER_CYCLE - (int) $offer->change_count);
@endphp

<div id="planet" class="detail">
    <div id="detail" class="detail_screen">
        <div id="header_text">
            <h2>{{ __('t_ingame.import_export.title') }}</h2>
        </div>

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
                        @if($isConsumed)
                            <div class="bargain_left_overlay" style="display:block;"></div>
                        @endif
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

                        @if($isConsumed)
                            {{-- Stato post-acquisto: testo OGame "Hai comprato 1 X. Per oggi non ci sono altre offerte. Torna domani!" --}}
                            <div class="bargain_overlay" style="display:block;">
                                <p class="got_item_text">{{ __('t_ingame.import_export.bought_1_item', ['name' => $item->name]) }}</p>
                                <p class="bargain_text">{{ __('t_ingame.import_export.no_more_today') }}</p>
                            </div>
                        @else
                            <form method="POST" action="{{ route('importexport.pay') }}" id="ie_form_pay">
                                @csrf
                                <input type="hidden" name="planet_id" value="{{ $currentPlanet->id }}">
                                <div class="payment">
                                    <div class="resourceSelection">
                                        <div class="selectWrapper">
                                            <a class="tooltip source planet js_planet selected" data-source-type="planet"></a>
                                            <a class="tooltip source moon js_moon" data-source-type="moon"></a>
                                            <a class="tooltip source star js_star" data-source-type="star"></a>
                                            <a id="js_toggleLinkImportExport" class="js_valSourcePlanet toggleHidden toggleLink">
                                                <img src="{{ asset('img/planets/small/' . ($currentPlanet->planet_type ?? 1) . '.gif') }}" alt="">
                                                <span class="option_source">{{ Str::limit($currentPlanet->name, 8, '...') }} [{{ $currentPlanet->galaxy }}:{{ $currentPlanet->system }}:{{ $currentPlanet->planet }}]</span>
                                            </a>
                                        </div>

                                        <table class="table_ressources">
                                            <tbody>
                                                @foreach([
                                                    ['key' => 'metal',     'mult' => '1',   'css' => 'metal',     'class' => 'normalResource', 'max' => $maxInputs['metal']],
                                                    ['key' => 'crystal',   'mult' => '1.5', 'css' => 'crystal',   'class' => 'normalResource', 'max' => $maxInputs['crystal']],
                                                    ['key' => 'deuterium', 'mult' => '3',   'css' => 'deuterium', 'class' => 'normalResource', 'max' => $maxInputs['deuterium']],
                                                    ['key' => 'honor',     'mult' => '100', 'css' => 'honor',     'class' => 'honorResource',  'max' => $maxInputs['honor']],
                                                ] as $row)
                                                    <tr class="{{ $row['class'] }}">
                                                        <td><div class="resourceIcon {{ $row['css'] }} resource_label"></div></td>
                                                        <td class="multiplier undermark tooltip"><span class="dark_highlight_tablet">x {{ $row['mult'] }}</span></td>
                                                        <td><input type="text" name="{{ $row['key'] }}" value="0" data-mult="{{ $row['mult'] }}" data-max="{{ $row['max'] }}" class="ie_input"></td>
                                                        <td><a class="ie_btn_inc" data-target="{{ $row['key'] }}">+</a></td>
                                                        <td><a class="ie_btn_max" data-target="{{ $row['key'] }}">&gt;&gt;</a></td>
                                                    </tr>
                                                    <tr class="{{ $row['class'] }}">
                                                        <td></td>
                                                        <td>
                                                            <div class="max_hint">({{ __('t_ingame.import_export.max_hint_prefix') }}
                                                                <span class="max_planet_res max_planet_{{ $row['key'] }}">{{ number_format($row['max'], 0, ',', '.') }}</span>)
                                                            </div>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <a class="pay disabled" id="ie_pay_btn">{{ __('t_ingame.import_export.pay_button') }}</a>
                                </div>
                            </form>

                            {{-- Bottoni Cambia / Prendi item --}}
                            <div class="bargain_buttons" style="text-align:center;margin-top:10px;">
                                <form method="POST" action="{{ route('importexport.change') }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="bargain import_bargain change" {{ $changesLeft <= 0 ? 'disabled' : '' }}>
                                        {{ __('t_ingame.import_export.change_button') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('importexport.take') }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="bargain import_bargain take">{{ __('t_ingame.import_export.take_item_button') }}</button>
                                </form>
                                <div class="bargain_cost" style="margin-top:6px;">
                                    {{ __('t_ingame.import_export.dm_cost_label', ['amount' => number_format($changeCost, 0, ',', '.')]) }}
                                    /
                                    {{ __('t_ingame.import_export.dm_cost_label', ['amount' => number_format($takeCost, 0, ',', '.')]) }}
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="right_footer"></div>
                </div>
            </div>
            <div class="footer"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var price = {{ (int) $offer->price }};
    var inputs = document.querySelectorAll('.ie_input');
    var totalEl = document.querySelector('.js_import_total');
    var payBtn = document.getElementById('ie_pay_btn');
    if (!totalEl || !payBtn) return;

    function recalc() {
        var total = 0;
        inputs.forEach(function (el) {
            var v = parseInt(el.value, 10) || 0;
            total += v * parseFloat(el.getAttribute('data-mult'));
        });
        total = Math.round(total);
        totalEl.textContent = total.toLocaleString('it-IT');
        if (total === price) {
            payBtn.classList.remove('disabled');
        } else {
            payBtn.classList.add('disabled');
        }
    }

    inputs.forEach(function (el) {
        el.addEventListener('input', recalc);
    });

    document.querySelectorAll('.ie_btn_max').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-target');
            // Set this row to its max (capped to price), zero the others
            inputs.forEach(function (el) {
                if (el.getAttribute('name') === key) {
                    var max = parseInt(el.getAttribute('data-max'), 10) || 0;
                    var mult = parseFloat(el.getAttribute('data-mult'));
                    el.value = Math.min(max, Math.floor(price / mult));
                } else {
                    el.value = 0;
                }
            });
            recalc();
        });
    });

    document.querySelectorAll('.ie_btn_inc').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-target');
            var current = 0;
            inputs.forEach(function (el) {
                current += (parseInt(el.value, 10) || 0) * parseFloat(el.getAttribute('data-mult'));
            });
            var remaining = price - Math.round(current);
            if (remaining <= 0) return;
            inputs.forEach(function (el) {
                if (el.getAttribute('name') !== key) return;
                var mult = parseFloat(el.getAttribute('data-mult'));
                var max = parseInt(el.getAttribute('data-max'), 10) || 0;
                var add = Math.min(max - (parseInt(el.value, 10) || 0), Math.floor(remaining / mult));
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
})();
</script>
@endsection
