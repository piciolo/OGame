<style>
    #traderOverview #div_traderAuctioneer > .header h2,
    #traderOverview #div_traderAuctioneer .left_header h2,
    #traderOverview #div_traderAuctioneer .right_header h2,
    #traderOverview #div_traderAuctioneer .history_header h2 {
        color: #6F9FC8 !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        line-height: 28px !important;
        height: 28px !important;
        padding: 0 !important;
        margin: 0 !important;
        text-align: center !important;
        background: none !important;
        width: auto !important;
    }
    #div_traderAuctioneer .stimulus {
        font-size: 11px;
        line-height: 14px;
        padding: 10px 20px;
        margin: 0;
        text-align: center;
    }
    #traderOverview #inhalt > #planet {
        height: 250px !important;
        position: relative;
    }
    #div_traderAuctioneer .stimulus a.help.rules {
        background: url('/img/icons/f5f81e8302aaad56c958c033677fb8.png') -207px -34px no-repeat;
        width: 18px;
        height: 18px;
        margin: 2px 5px 0 0;
        float: left;
        display: block;
    }
    /* closed toggle link — img + span alignment */
    #div_traderAuctioneer #js_toggleLinkAuctioneer img {
        display: inline-block;
        vertical-align: middle;
        margin: 7px 4px 0 0;
        float: left;
    }
    #div_traderAuctioneer #js_toggleLinkAuctioneer .option_source {
        display: inline-block;
        vertical-align: middle;
        line-height: 31px;
        height: 31px;
        text-align: left;
    }
    /* summary table — match official: 400px wide, margin-right 20px */
    #div_traderAuctioneer .table_ressources_sum { width: 400px !important; margin: 0 20px 0 0 !important; table-layout: auto; }
    #div_traderAuctioneer .table_ressources_sum td:first-child { width: 157px; text-align: right; padding: 0 3px 0 0; }
    #div_traderAuctioneer .table_ressources_sum td:last-child  { text-align: right; padding: 0 3px 0 0; }
    /* planet dropdown — match official compact styling */
    #div_traderAuctioneer #js_togglePanelAuctioneer {
        background: #181F26 !important;
        padding: 4px !important;
        width: 193px !important;
        margin: 0 38px 0 0 !important;
        border: 1px solid #384C5F;
        z-index: 30;
    }
    #div_traderAuctioneer #js_togglePanelAuctioneer ul {
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #div_traderAuctioneer #js_togglePanelAuctioneer li {
        list-style: none !important;
        display: block !important;
        height: 15px !important;
        line-height: 15px !important;
        padding: 0 0 0 3px !important;
        margin: 0 !important;
        font-size: 12px !important;
        color: #848484 !important;
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
    }
    #div_traderAuctioneer #js_togglePanelAuctioneer li:hover,
    #div_traderAuctioneer #js_togglePanelAuctioneer li.selected {
        background: #2B3B4C !important;
        color: #C8D4E0 !important;
    }
    #div_traderAuctioneer #js_togglePanelAuctioneer li img {
        width: 12px;
        height: 12px;
        vertical-align: middle;
        margin-right: 4px;
    }
    #div_traderAuctioneer #js_togglePanelAuctioneer li .option_source {
        display: inline-block !important;
        width: 165px !important;
        height: 15px !important;
        line-height: 15px !important;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

@php
    $hasAuction = $auction !== null;
    $status = $hasAuction ? $auction->status->value : 'none';
    $isRunning = $status === 'running';
    $isWaiting = $status === 'waiting';
    $currentBid = $hasAuction ? (int) $auction->current_bid_points : 0;
    $minBid = $hasAuction ? max((int) $auction->min_bid_points, $currentBid + $minIncrement) : 0;
    $tierMap = ['bronze' => 'r_common', 'silver' => 'r_uncommon', 'gold' => 'r_rare', 'platinum' => 'r_epic'];
    $tier = $hasAuction ? ($tierMap[$auction->tier->value] ?? 'r_common') : 'r_common';
    $tierLarge = $tier . '_140px';
    $cp = collect($planets)->first(fn($p) => $p->getPlanetId() === $currentPlanetId);
    $endTs = $isRunning ? ($auction->ends_at?->timestamp ?? 0) : ($auction->waiting_ends_at?->timestamp ?? 0);
@endphp

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
                <div id="loadingOverlay" style="display:none">
                    <img src="/img/icons/4161a64a933a5345d00cb9fdaa25c7.gif" alt="load...">
                </div>
                <div id="header_text" style="display: block; background-position: 0px 0px;">
                    <h2>{{ __('t_auctioneer.title') }}</h2>
                    <a class="back_to_overview js_backToOverview tooltip js_hideTipOnMobile left" href="{{ route('merchant.index') }}" title="{{ __('t_merchant.back') }}" style="display: inline;"></a>
                    <a class="small_back_to_overview js_backToOverview tooltip js_hideTipOnMobile" href="{{ route('merchant.index') }}" title="{{ __('t_merchant.back') }}"></a>
                </div>
            </div>

            <div class="c-right c-small"></div>

            <div id="div_traderAuctioneer" class="div_trader">
                    <div class="header">
                        <h2>{{ __('t_auctioneer.title') }}</h2>
                    </div>
                    <div class="content">
                        <p class="stimulus">
                            <a class="tooltipHTML help rules" title="{{ __('t_auctioneer.title') }}|{{ __('t_auctioneer.description') }}"></a>
                            {{ __('t_auctioneer.description_line1') }}<br>
                            {{ __('t_auctioneer.description_line2') }} <span style="font-weight:bold;"><span style="color:#99CC00;font-weight:bold;">{{ __('t_auctioneer.description_warning') }}</span></span>
                        </p>

                        <div class="left_box">
                            <div class="left_header">
                                <h2 id="auctionState">
                                    @if ($isRunning) {{ __('t_auctioneer.auction_in_progress') }}
                                    @elseif ($isWaiting) {{ __('t_auctioneer.auction_waiting') }}
                                    @else {{ __('t_auctioneer.auction_completed') }}
                                    @endif
                                </h2>
                            </div>
                            <div class="left_content">
                                @if (!$isRunning)
                                    <div class="overlay"></div>
                                @endif
                                <div class="image_140px">
                                    @php
                                        $lotTip = '';
                                        $lotRef = '';
                                        $lotSprite = null;
                                        if ($hasAuction) {
                                            $lt = $auction->lot_type->value;
                                            $p = (array) ($auction->lot_payload ?? []);

                                            // Italian display title derived from payload
                                            $displayTitle = $auction->lot_title;
                                            if ($lt === 'resources') {
                                                $tp = [];
                                                if (!empty($p['metal']))     $tp[] = number_format((int)$p['metal'], 0, ',', '.') . ' ' . __('t_auctioneer.metal');
                                                if (!empty($p['crystal']))   $tp[] = number_format((int)$p['crystal'], 0, ',', '.') . ' ' . __('t_auctioneer.crystal');
                                                if (!empty($p['deuterium'])) $tp[] = number_format((int)$p['deuterium'], 0, ',', '.') . ' ' . __('t_auctioneer.deuterium');
                                                if (!empty($tp)) $displayTitle = implode(' + ', $tp);
                                            } elseif ($lt === 'dark_matter') {
                                                $displayTitle = number_format((int)($p['amount'] ?? 0), 0, ',', '.') . ' ' . __('t_auctioneer.dark_matter');
                                            } elseif (str_starts_with($lt, 'booster_') || $lt === 'resource_boost') {
                                                $bn = ['booster_kraken' => 'Kraken', 'booster_newtron' => 'Newtron', 'booster_detroid' => 'Detroid', 'resource_boost' => 'Produzione'];
                                                $tl = ['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum'];
                                                $displayTitle = ($bn[$lt] ?? ucfirst(str_replace('booster_', '', $lt))) . ' ' . ($tl[$auction->tier->value] ?? '');
                                            }

                                            // Description
                                            $descr = '';
                                            if ($lt === 'resources') {
                                                $dp = [];
                                                if (!empty($p['metal']))     $dp[] = number_format((int)$p['metal'], 0, ',', '.') . ' ' . __('t_auctioneer.metal');
                                                if (!empty($p['crystal']))   $dp[] = number_format((int)$p['crystal'], 0, ',', '.') . ' ' . __('t_auctioneer.crystal');
                                                if (!empty($p['deuterium'])) $dp[] = number_format((int)$p['deuterium'], 0, ',', '.') . ' ' . __('t_auctioneer.deuterium');
                                                $descr = __('t_auctioneer.tooltip_receive') . ' ' . implode(', ', $dp) . ' ' . __('t_auctioneer.tooltip_on_planet');
                                            } elseif ($lt === 'ship') {
                                                $descr = __('t_auctioneer.tooltip_receive') . ' ' . (int)($p['amount'] ?? 1) . ' ' . __('t_auctioneer.tooltip_units_delivered');
                                            } elseif ($lt === 'dark_matter') {
                                                $descr = __('t_auctioneer.tooltip_receive') . ' ' . number_format((int)($p['amount'] ?? 0), 0, ',', '.') . ' ' . __('t_auctioneer.tooltip_dm_credited');
                                            } elseif (str_starts_with($lt, 'booster_') || $lt === 'resource_boost') {
                                                $bd = ['booster_kraken' => __('t_auctioneer.booster_kraken_desc'), 'booster_newtron' => __('t_auctioneer.booster_newtron_desc'), 'booster_detroid' => __('t_auctioneer.booster_detroid_desc'), 'resource_boost' => '+' . ($p['percent'] ?? 0) . '% ' . __('t_auctioneer.tooltip_production') . ' ' . ($p['resource'] ?? '')];
                                                $descr = $bd[$lt] ?? __('t_auctioneer.booster_generic_desc');
                                            }

                                            // Duration from payload
                                            $durLbl = '—';
                                            if (!empty($p['duration_seconds'])) {
                                                $secs = (int)$p['duration_seconds'];
                                                if ($secs >= 86400)     $durLbl = floor($secs / 86400) . 'g';
                                                elseif ($secs >= 3600)  $durLbl = floor($secs / 3600) . 'h';
                                                elseif ($secs >= 60)    $durLbl = floor($secs / 60) . 'm';
                                                else                    $durLbl = $secs . 's';
                                            } elseif (in_array($lt, ['resources', 'dark_matter', 'ship'])) {
                                                $durLbl = __('t_auctioneer.tooltip_instant');
                                            }

                                            $lotTip = $displayTitle . '|' . $descr
                                                . '<br><br>' . __('t_auctioneer.tooltip_duration') . ': ' . $durLbl
                                                . '<br>' . __('t_auctioneer.tooltip_price') . ': —'
                                                . '<br>' . __('t_auctioneer.tooltip_inventory') . ': 0';

                                            // Deterministic image key: same lot_type+title → same sprite
                                            $lotRef = sha1($lt . '|' . $auction->lot_title);

                                            // Boosters use lot_*.png; others use controller-derived lot_image
                                            if (str_starts_with($lt, 'booster_') || $lt === 'resource_boost') {
                                                $spriteFiles = glob(public_path('img/auctioneer/lot_*.png')) ?: [];
                                                if (!empty($spriteFiles)) {
                                                    $idx = (int) hexdec(substr($lotRef, 0, 8)) % count($spriteFiles);
                                                    $lotSprite = '/img/auctioneer/' . basename($spriteFiles[$idx]);
                                                }
                                            }
                                            if ($lotSprite === null) {
                                                $lotSprite = $auction->lot_image ?: null;
                                            }
                                        }
                                        $tierBorder = ['r_rare_140px' => '#FFD700', 'r_uncommon_140px' => '#C0C0C0', 'r_common_140px' => '#CD7F32', 'r_epic_140px' => '#B9F2FF'];
                                        $borderColor = $tierBorder[$tierLarge] ?? '#CD7F32';
                                    @endphp
                                    @php
                                        $tierFrameOffsets = ['r_rare_140px' => '0px 0px', 'r_uncommon_140px' => '-141px 0px', 'r_common_140px' => '-282px 0px', 'r_epic_140px' => '-564px 0px'];
                                        $framePos = $tierFrameOffsets[$tierLarge] ?? '-282px 0px';
                                    @endphp
                                    <a class="detail_button tooltipHTML js_hideTipOnMobile slideIn {{ $tierLarge }}"
                                       href="javascript:void(0);"
                                       style="position:relative;display:block;width:140px;height:140px;background:#000;box-sizing:border-box;"
                                       @if ($hasAuction) ref="{{ $lotRef }}" title="{{ $lotTip }}" @else title="" @endif>
                                        @if ($lotSprite)
                                            <img src="{{ $lotSprite }}" alt="{{ $auction->lot_title }}" style="position:absolute;top:0;left:0;width:140px;height:140px;z-index:1;object-fit:cover;">
                                        @endif
                                        {{-- tier frame overlay --}}
                                        <span style="position:absolute;top:0;left:0;width:140px;height:140px;z-index:2;background:url('/img/auctioneer/tier_frames_140.png') {{ $framePos }} no-repeat;display:block;pointer-events:none;"></span>
                                        <span class="ecke" style="position:absolute;bottom:2px;right:6px;z-index:3;color:#FFA500;font-weight:bold;text-shadow:0 0 3px #000;">
                                            <span class="level amount">{{ $hasAuction ? (int) $auction->bid_count : 0 }}</span>
                                        </span>
                                    </a>
                                </div>
                                @if ($isRunning)
                                    <p class="auction_info">{{ __('t_auctioneer.auction_ends_in') }}
                                        <span style="color:#99CC00"><span style="font-weight:bold;" id="auctionCountdown"
                                              data-end-timestamp="{{ $endTs }}"
                                              data-server-now="{{ $serverNow }}"
                                              data-mode="fuzzy">—</span></span>
                                    </p>
                                @elseif ($isWaiting)
                                    <p class="auction_info">{{ __('t_auctioneer.next_auction_in') }}<br>
                                        <span class="nextAuction" id="auctionCountdown"
                                              data-end-timestamp="{{ $endTs }}"
                                              data-server-now="{{ $serverNow }}"
                                              data-mode="exact">—</span>
                                    </p>
                                @else
                                    <p class="auction_info">&nbsp;</p>
                                @endif
                                <label class="auction_detail odd info_label">{{ __('t_auctioneer.current_bid') }}</label>
                                <div class="detail_value odd currentSum" id="currentBid">{{ number_format($currentBid, 0, ',', '.') }}</div>
                                <label class="auction_detail even info_label">{{ __('t_auctioneer.num_bids') }}</label>
                                <div class="detail_value even numberOfBids" id="bidCount">{{ $hasAuction ? (int) $auction->bid_count : 0 }}</div>
                                <label class="auction_detail odd info_label">{{ __('t_auctioneer.highest_bidder') }}</label>
                                <a class="detail_value odd currentPlayer" id="highestBidder">{{ $hasAuction && $auction->current_bidder_name ? $auction->current_bidder_name : '—' }}</a>
                            </div>
                            <div class="left_footer"></div>
                        </div>

                        <div class="right_box">
                            @if (!$isRunning)
                                <div class="noAuctionOverlay"></div>
                            @endif
                            <div class="right_header">
                                <h2>{{ __('t_auctioneer.submit_bid') }}</h2>
                            </div>
                            <div class="right_content">
                                <div class="resourceSelection">
                                    <div class="selectWrapper">
                                        <a class="tooltip js_hideTipOnMobile source planet js_planet selected" title="{{ __('t_auctioneer.select_planet') }}"></a>
                                        <a class="tooltip js_hideTipOnMobile source moon js_moon" title="{{ __('t_auctioneer.moon_tooltip') }}"></a>
                                        <a class="tooltip js_hideTipOnMobile source honor js_honor" title="{{ __('t_auctioneer.honor_tooltip') }}"></a>
                                        @php
                                            $cpIcon = '/img/planets/small/normal_1.png';
                                            if ($cp) {
                                                if ($cp->isPlanet()) {
                                                    $cpIcon = '/img/planets/small/' . $cp->getPlanetBiomeType() . '_' . $cp->getPlanetImageType() . '.png';
                                                } else {
                                                    $cpIcon = '/img/moons/small/1.gif';
                                                }
                                            }
                                        @endphp
                                        <a id="js_toggleLinkAuctioneer" class="js_valSourcePlanet toggleHidden toggleLink" href="#togglePanel" title="{{ __('t_auctioneer.select_planet') }}">
                                            <img src="{{ $cpIcon }}" width="18" height="18" alt="">
                                            <span class="option_source">{{ $cp?->getPlanetName() ?? '—' }}@if($cp) [{{ $cp->getPlanetCoordinates()->asString() }}]@endif</span>
                                        </a>
                                    </div>
                                    <div id="js_togglePanelAuctioneer" class="togglePanel" style="display:none">
                                        @php
                                            $planetItems = array_filter($planets, fn($p) => $p->isPlanet());
                                            $moonItems = array_filter($planets, fn($p) => !$p->isPlanet());
                                        @endphp
                                        <ul class="planet active">
                                            @foreach ($planetItems as $p)
                                                @php $icon = '/img/planets/small/' . $p->getPlanetBiomeType() . '_' . $p->getPlanetImageType() . '.png'; @endphp
                                                <li id="{{ $p->getPlanetId() }}"
                                                    data-planet-id="{{ $p->getPlanetId() }}"
                                                    data-metal="{{ (int) floor($p->metal()->get()) }}"
                                                    data-crystal="{{ (int) floor($p->crystal()->get()) }}"
                                                    data-deuterium="{{ (int) floor($p->deuterium()->get()) }}"
                                                    class="dark_highlight_tablet planet_select_item @if ($p->getPlanetId() === $currentPlanetId) selected active @endif">
                                                    <img src="{{ $icon }}" width="12" height="12" alt="">
                                                    <span class="option_source">{{ $p->getPlanetName() }} [{{ $p->getPlanetCoordinates()->asString() }}]</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if (!empty($moonItems))
                                            <ul class="moon active" style="display:none">
                                                @foreach ($moonItems as $p)
                                                    <li id="{{ $p->getPlanetId() }}"
                                                        data-planet-id="{{ $p->getPlanetId() }}"
                                                        data-metal="{{ (int) floor($p->metal()->get()) }}"
                                                        data-crystal="{{ (int) floor($p->crystal()->get()) }}"
                                                        data-deuterium="{{ (int) floor($p->deuterium()->get()) }}"
                                                        class="dark_highlight_tablet planet_select_item @if ($p->getPlanetId() === $currentPlanetId) selected active @endif">
                                                        <img src="/img/moons/small/1.gif" width="12" height="12" alt="">
                                                        <span class="option_source">{{ $p->getPlanetName() }} [{{ $p->getPlanetCoordinates()->asString() }}]</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                    <input type="hidden" id="bidPlanet" value="{{ $currentPlanetId }}">
                                </div>

                                <table class="table_ressources" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        @php
                                            $rows = [
                                                ['metal', $rates['metal']],
                                                ['crystal', $rates['crystal']],
                                                ['deuterium', $rates['deuterium']],
                                            ];
                                        @endphp
                                        @foreach ($rows as [$res, $rate])
                                            @php
                                                $rateLbl = rtrim(rtrim(number_format((float) $rate, 1, '.', ''), '0'), '.') ?: (string) (int) $rate;
                                                $cpMax = $cp ? (int) floor($cp->{$res}()->get()) : 0;
                                            @endphp
                                            <tr class="normalResource" data-row-type="normal">
                                                <td><div class="resourceIcon {{ $res }} resource_label tooltip" title="{{ __('t_auctioneer.' . $res) }}"></div></td>
                                                <td class="multiplier undermark tooltip" title="x{{ $rateLbl }}"><span class="dark_highlight_tablet">x {{ $rateLbl }}</span></td>
                                                <td><input type="text" class="resourceAmount js_amount js_slider{{ ucfirst($res) }}Input js_{{ $res }} checkThousandSeparator hideNumberSpin bid_input" data-resource="{{ $res }}" value="0" maxlength="14" autocomplete="off"></td>
                                                <td><a href="javascript:void(0);" class="value-control more js_slider{{ ucfirst($res) }}More js_valButton tooltip js_hideTipOnMobile bid_more" data-resource="{{ $res }}" title="+">+</a></td>
                                                <td><a href="javascript:void(0);" class="value-control max js_slider{{ ucfirst($res) }}Max js_valButton tooltipRight js_hideTipOnMobile bid_max" data-resource="{{ $res }}" title="{{ __('t_auctioneer.max') }}">&gt;&gt;</a></td>
                                            </tr>
                                            <tr class="max_hint_row" data-row-type="normal">
                                                <td></td>
                                                <td colspan="3"><div class="max_hint" data-resource="{{ $res }}">(max. <span class="js_max_{{ $res }}">{{ number_format($cpMax, 0, ',', '.') }}</span>)</div></td>
                                                <td></td>
                                            </tr>
                                        @endforeach
                                        @php
                                            $honorRateLbl = rtrim(rtrim(number_format((float) $rates['honor'], 1, '.', ''), '0'), '.') ?: (string) (int) $rates['honor'];
                                        @endphp
                                        <tr class="honorResource" data-row-type="honor" style="display:none">
                                            <td><img src="/img/auctioneer/honor_points_large.png" alt="{{ __('t_auctioneer.honor_points') }}" class="resource_label"></td>
                                            <td class="multiplier undermark tooltip" title="x{{ $honorRateLbl }}"><span class="dark_highlight_tablet">x {{ $honorRateLbl }}</span></td>
                                            <td><input type="text" class="resourceAmount js_amount js_sliderHonorInput js_honor checkThousandSeparator hideNumberSpin bid_input" data-resource="honor" value="0" maxlength="14" autocomplete="off"></td>
                                            <td><a href="javascript:void(0);" class="value-control more js_sliderHonorMore js_valButton tooltip js_hideTipOnMobile bid_more" data-resource="honor" title="+1000">+</a></td>
                                            <td><a href="javascript:void(0);" class="value-control max js_sliderHonorMax js_valButton tooltipRight js_hideTipOnMobile bid_max" data-resource="honor" title="{{ __('t_auctioneer.max') }}">&gt;&gt;</a></td>
                                        </tr>
                                        <tr class="max_hint_row" data-row-type="honor" style="display:none">
                                            <td></td>
                                            <td colspan="3"><div class="max_hint" data-resource="honor">(max. <span class="js_max_honor">{{ number_format((int) $honorPoints, 0, ',', '.') }}</span>)</div></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table class="table_ressources_sum" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr class="even">
                                            <td class="auctionInfo js_bidRise tooltip" title="{{ __('t_auctioneer.bid_increased_by') }}">{{ __('t_auctioneer.bid_increased_by') }}</td>
                                            <td class="auctionInfo sum js_auctioneerSum green_text tooltip" id="summaryDelta">0</td>
                                        </tr>
                                        <tr class="odd">
                                            <td class="auctionInfo tooltip" title="{{ __('t_auctioneer.your_bid') }}">{{ __('t_auctioneer.your_bid') }}</td>
                                            <td class="auctionInfo js_alreadyBidden" id="summaryYours">0</td>
                                        </tr>
                                        <tr class="odd">
                                            <td class="auctionInfo tooltip" title="{{ __('t_auctioneer.minimum_bid') }}">{{ __('t_auctioneer.minimum_bid') }}</td>
                                            <td class="auctionInfo js_price" id="summaryMinimum">{{ number_format($minBid, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr class="even" id="summaryShortfallRow" style="display:none">
                                            <td class="auctionInfo tooltip" title="{{ __('t_auctioneer.shortfall') }}">{{ __('t_auctioneer.shortfall') }}</td>
                                            <td class="auctionInfo js_deficit" id="summaryShortfall">0</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <p class="overbid_text" id="overbidText" style="display:none">{{ __('t_auctioneer.bid_rejected') }}</p>

                                <a href="javascript:void(0);" id="submitBidBtn" class="pay @if (!$isRunning) disabled @endif">{{ __('t_auctioneer.submit_bid') }}</a>
                                <div id="bidFeedback" class="bid_feedback" style="display:none"></div>
                                <br class="clearfloat">
                            </div>
                        </div>

                        <div class="auction_history">
                            <div class="history_header">
                                <h2>{{ __('t_auctioneer.previous_auctions') }}</h2>
                            </div>
                            <div class="history_content">
                                <ul>
                                    @forelse ($history as $i => $h)
                                        @php
                                            $rClass = $tierMap[$h['tier']] ?? 'r_common';
                                            $parity = $i % 2 === 0 ? 'even' : 'odd';
                                            $moreClass = $i >= 3 ? ' more_auctions_li' : '';
                                        @endphp
                                        <li class="{{ $parity }}{{ $moreClass }}" @if ($i >= 3) style="display:none" @endif>
                                            <a class="slideIn" title="{{ $h['lot_title'] }}">
                                                @if (!empty($h['lot_image']))
                                                    <img height="30" width="30" alt="" src="{{ $h['lot_image'] }}" class="item_img tooltipHTML tooltipLeft {{ $rClass }}" title="{{ $h['lot_title'] }}">
                                                @else
                                                    <img height="30" width="30" alt="" src="/img/objects/units/crawler_small.jpg" class="item_img tooltipHTML tooltipLeft {{ $rClass }}" title="{{ $h['lot_title'] }}">
                                                @endif
                                            </a>
                                            @if ($h['sold'])
                                                <span class="detail sum">{{ number_format($h['winning_bid'], 0, ',', '.') }}</span>
                                                <span class="detail player"><a>{{ $h['winner_name'] }}</a></span>
                                            @else
                                                <span class="detail sum">{{ __('t_auctioneer.not_sold') }}</span>
                                                <span class="detail player"></span>
                                            @endif
                                            <span class="detail date_time">{{ $h['closed_at'] }}</span>
                                        </li>
                                    @empty
                                        <li class="even">
                                            <span class="detail sum">{{ __('t_auctioneer.no_history') }}</span>
                                        </li>
                                    @endforelse
                                </ul>
                                @if (count($history) > 3)
                                    <a href="javascript:void(0);" class="more dark_highlight_tablet" id="historyMore">{{ __('t_auctioneer.show_more') }}</a>
                                    <br class="clearfloat">
                                @endif
                            </div>
                            <div class="history_footer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    const CSRF = '{{ csrf_token() }}';
    const RATES = @json($rates);
    const MIN_INCREMENT = {{ (int) $minIncrement }};
    const URL_STATUS = '{{ route('auctioneer.status') }}';
    const URL_BID = '{{ route('auctioneer.bid') }}';
    const T_ABOUT = '{{ __('t_auctioneer.about') }}';
    const T_ACCEPTED = @json(__('t_auctioneer.bid_accepted'));
    const T_REJECTED = @json(__('t_auctioneer.bid_rejected'));
    const T_NETERR = @json(__('t_auctioneer.network_error'));

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const fmt = n => Math.round(Number(n)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const parseNum = s => parseInt(String(s).replace(/[^\d]/g, ''), 10) || 0;

    // Fuzzy format: "circa Xh" / "circa Xm" / "Xs"
    function fuzzyTime(s) {
        if (s <= 0) return '0s';
        if (s < 60)  return s + 's';
        if (s < 3600) return T_ABOUT + ' ' + Math.floor(s / 60) + 'm';
        return T_ABOUT + ' ' + Math.floor(s / 3600) + 'h';
    }
    // Exact format: "Xh Ym Zs" / "Ym Zs" / "Zs"
    function exactTime(s) {
        if (s <= 0) return '0s';
        const h = Math.floor(s/3600), m = Math.floor((s%3600)/60), z = s%60;
        if (h) return h+'h '+m+'m '+z+'s';
        if (m) return m+'m '+z+'s';
        return z+'s';
    }

    const countdownEl = $('#auctionCountdown');
    let clientOffset = 0;
    const syncClock = sv => clientOffset = (Date.now()/1000) - sv;
    function tickCountdown() {
        if (!countdownEl) return;
        const end = parseInt(countdownEl.dataset.endTimestamp||'0',10);
        if (!end) return;
        const now = (Date.now()/1000) - clientOffset;
        const r = Math.max(0, Math.floor(end-now));
        countdownEl.textContent = (countdownEl.dataset.mode === 'exact') ? exactTime(r) : fuzzyTime(r);
        if (r === 0) refreshStatus();
    }
    if (countdownEl) { syncClock(+countdownEl.dataset.serverNow||0); setInterval(tickCountdown,1000); tickCountdown(); }

    async function refreshStatus() {
        try {
            const r = await fetch(URL_STATUS, {cache:'no-store', headers:{'Accept':'application/json'}});
            if (!r.ok) return;
            const d = await r.json();
            if (d.status === 'none') { location.reload(); return; }
            const prev = countdownEl?.dataset.endTimestamp;
            const ne = d.ends_at || d.waiting_ends_at;
            if (countdownEl && String(ne) !== prev) { location.reload(); return; }
            syncClock(d.server_now);
            const cb = $('#currentBid'); if (cb) cb.textContent = fmt(d.current_bid_points);
            const bc = $('#bidCount'); if (bc) bc.textContent = d.bid_count;
            const hb = $('#highestBidder'); if (hb) hb.textContent = d.current_bidder_name || '—';
            const sm = $('#summaryMinimum');
            if (sm) sm.textContent = fmt(Math.max(d.min_bid_points, d.current_bid_points + MIN_INCREMENT));
            recomputeSummary();
        } catch (e) {}
    }
    setInterval(refreshStatus, 5000);

    const HONOR_POINTS = {{ (int) $honorPoints }};
    function calcPoints(m,c,d,h) { return Math.floor(m*RATES.metal + c*RATES.crystal + d*RATES.deuterium + (h||0)*(RATES.honor||0)); }
    function recomputeSummary() {
        const m = parseNum($('.bid_input[data-resource=metal]')?.value);
        const c = parseNum($('.bid_input[data-resource=crystal]')?.value);
        const d = parseNum($('.bid_input[data-resource=deuterium]')?.value);
        const h = parseNum($('.bid_input[data-resource=honor]')?.value);
        const yours = calcPoints(m,c,d,h);
        const minBid = parseNum($('#summaryMinimum')?.textContent);
        const cur = parseNum($('#currentBid')?.textContent);
        const delta = yours - cur;
        const el = id => $('#'+id);
        if (el('summaryDelta')) el('summaryDelta').textContent = fmt(delta);
        if (el('summaryYours')) el('summaryYours').textContent = fmt(yours);
        const sf = Math.max(0, minBid - yours);
        const row = $('#summaryShortfallRow');
        if (row) row.style.display = sf>0?'':'none';
        if (el('summaryShortfall')) el('summaryShortfall').textContent = fmt(sf);
        const pay = $('#submitBidBtn');
        if (pay) {
            if (yours >= minBid && yours > 0) pay.classList.remove('disabled');
            else pay.classList.add('disabled');
        }
    }
    function currentPlanetData() {
        const id = $('#bidPlanet')?.value;
        const li = $('#js_togglePanelAuctioneer li[data-planet-id="'+id+'"]');
        return li ? {metal:+li.dataset.metal, crystal:+li.dataset.crystal, deuterium:+li.dataset.deuterium, name: li.querySelector('.planet_name')?.textContent} : null;
    }
    $$('.bid_input').forEach(i => i.addEventListener('input', recomputeSummary));
    $$('.bid_max').forEach(btn => btn.addEventListener('click', () => {
        const r = btn.dataset.resource;
        const input = $(`.bid_input[data-resource=${r}]`);
        if (!input) return;
        if (r === 'honor') { input.value = HONOR_POINTS; recomputeSummary(); return; }
        const p = currentPlanetData(); if (!p) return;
        input.value = p[r]; recomputeSummary();
    }));
    $$('.bid_more').forEach(btn => btn.addEventListener('click', () => {
        const r = btn.dataset.resource;
        const input = $(`.bid_input[data-resource=${r}]`);
        if (!input) return;
        const cur = parseNum(input.value);
        const step = r === 'honor' ? 1000 : Math.max(100, Math.floor(cur * 0.1) || 100);
        input.value = cur + step;
        recomputeSummary();
    }));

    function setSource(kind) {
        if (kind === 'moon') {
            const moons = $$('#js_togglePanelAuctioneer ul.moon li');
            if (moons.length === 0) return;
        }
        $$('.selectWrapper .source').forEach(el => el.classList.remove('selected'));
        const src = $('.selectWrapper .source.' + kind);
        if (src) src.classList.add('selected');
        const isHonor = kind === 'honor';
        $$('tr[data-row-type=normal]').forEach(tr => tr.style.display = isHonor ? 'none' : '');
        $$('tr[data-row-type=honor]').forEach(tr => tr.style.display = isHonor ? '' : 'none');
        const toggle = $('#js_toggleLinkAuctioneer');
        if (toggle) toggle.style.visibility = isHonor ? 'hidden' : '';
        const panel = $('#js_togglePanelAuctioneer');
        if (panel) panel.style.display = 'none';
        if (isHonor) {
            ['metal','crystal','deuterium'].forEach(r => { const i = $(`.bid_input[data-resource=${r}]`); if (i) i.value = '0'; });
        } else {
            const i = $('.bid_input[data-resource=honor]'); if (i) i.value = '0';
        }
        if (toggle) {
            toggle.classList.toggle('honor', isHonor);
            toggle.classList.toggle('moon', kind === 'moon');
            toggle.classList.toggle('planet', kind === 'planet');
        }
        if (kind === 'moon' || kind === 'planet') {
            const planetsUl = $('#js_togglePanelAuctioneer ul.planet');
            const moonsUl = $('#js_togglePanelAuctioneer ul.moon');
            if (planetsUl) planetsUl.style.display = kind === 'moon' ? 'none' : '';
            if (moonsUl) moonsUl.style.display = kind === 'moon' ? '' : 'none';

            const activeUlSel = kind === 'moon' ? 'ul.moon' : 'ul.planet';
            const items = $$('#js_togglePanelAuctioneer ' + activeUlSel + ' li');
            const currentInList = items.find(li => li.dataset.planetId === $('#bidPlanet')?.value);
            const target = currentInList || items[0];
            if (target) {
                $('#bidPlanet').value = target.dataset.planetId;
                items.forEach(x => { x.classList.remove('active'); x.classList.remove('selected'); });
                target.classList.add('active');
                target.classList.add('selected');
                const linkSpan = $('#js_toggleLinkAuctioneer .option_source');
                const linkImg = $('#js_toggleLinkAuctioneer img');
                const srcSpan = target.querySelector('.option_source');
                const srcImg = target.querySelector('img');
                if (linkSpan && srcSpan) linkSpan.textContent = srcSpan.textContent;
                if (linkImg && srcImg) linkImg.src = srcImg.src;
                updateMaxHints();
            }
        }
        recomputeSummary();
    }
    $('.js_planet')?.addEventListener('click', (e) => { e.preventDefault(); setSource('planet'); });
    $('.js_moon')?.addEventListener('click', (e) => { e.preventDefault(); setSource('moon'); });
    $('.js_honor')?.addEventListener('click', (e) => { e.preventDefault(); setSource('honor'); });
    $('#js_toggleLinkAuctioneer')?.addEventListener('click', (e) => {
        e.preventDefault();
        const panel = $('#js_togglePanelAuctioneer');
        if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });
    function updateMaxHints() {
        const p = currentPlanetData(); if (!p) return;
        ['metal','crystal','deuterium'].forEach(r => {
            const el = $('.js_max_'+r);
            if (el) el.textContent = fmt(p[r]);
        });
    }
    $$('#js_togglePanelAuctioneer li').forEach(li => li.addEventListener('click', () => {
        $('#bidPlanet').value = li.dataset.planetId;
        $$('#js_togglePanelAuctioneer li').forEach(x => { x.classList.remove('active'); x.classList.remove('selected'); });
        li.classList.add('active');
        li.classList.add('selected');
        const linkSpan = $('#js_toggleLinkAuctioneer .option_source');
        const linkImg = $('#js_toggleLinkAuctioneer img');
        const srcSpan = li.querySelector('.option_source');
        const srcImg = li.querySelector('img');
        if (linkSpan && srcSpan) linkSpan.textContent = srcSpan.textContent;
        if (linkImg && srcImg) linkImg.src = srcImg.src;
        $('#js_togglePanelAuctioneer').style.display = 'none';
        updateMaxHints();
    }));
    const moreBtn = $('#historyMore');
    if (moreBtn) moreBtn.addEventListener('click', () => {
        $$('.history_content .more_auctions_li').forEach(el => el.style.display = 'list-item');
        moreBtn.style.display = 'none';
    });
    recomputeSummary();

    $('#submitBidBtn')?.addEventListener('click', async () => {
        const btn = $('#submitBidBtn');
        if (!btn || btn.dataset.busy || btn.classList.contains('disabled')) return;
        btn.dataset.busy = '1';
        const fb = $('#bidFeedback');
        if (fb) { fb.style.display = 'none'; fb.className = 'bid_feedback'; }
        const form = new FormData();
        form.append('_token', CSRF);
        form.append('planet_id', $('#bidPlanet').value);
        form.append('metal', parseNum($('.bid_input[data-resource=metal]').value));
        form.append('crystal', parseNum($('.bid_input[data-resource=crystal]').value));
        form.append('deuterium', parseNum($('.bid_input[data-resource=deuterium]').value));
        form.append('honor', parseNum($('.bid_input[data-resource=honor]')?.value || 0));
        try {
            const r = await fetch(URL_BID, {method:'POST', body:form, headers:{'Accept':'application/json'}});
            const data = await r.json();
            if (data.success) {
                if (fb) { fb.className = 'bid_feedback success'; fb.textContent = T_ACCEPTED; fb.style.display = ''; }
                $$('.bid_input').forEach(i => { i.value = '0'; });
                await refreshStatus();
                setTimeout(() => location.reload(), 800);
            } else {
                if (fb) { fb.className = 'bid_feedback error'; fb.textContent = data.message || T_REJECTED; fb.style.display = ''; }
                const ov = $('#overbidText'); if (ov) ov.style.display = '';
            }
        } catch (e) {
            if (fb) { fb.className = 'bid_feedback error'; fb.textContent = T_NETERR; fb.style.display = ''; }
        } finally { delete btn.dataset.busy; }
    });

    // Slide back to merchant overview
    document.querySelectorAll('.js_backToOverview').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (!href || href === 'javascript:void(0)' || href === '#') return;
            e.preventDefault();
            const wrapper = document.getElementById('contentWrapper');
            if (wrapper) {
                wrapper.style.transition = 'transform 0.25s ease, opacity 0.25s ease';
                wrapper.style.transform = 'translateX(50px)';
                wrapper.style.opacity = '0';
                sessionStorage.setItem('auctioneer_back', '1');
                setTimeout(function () { window.location.href = href; }, 260);
            } else {
                window.location.href = href;
            }
        });
    });
})();
</script>
