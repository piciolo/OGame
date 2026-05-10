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
    /* detail overlay — hidden by default, populated by JS on history slideIn click */
    #traderOverview #detail.detail_screen { display: none !important; }
    #traderOverview #detail.detail_screen.active { display: block !important; }
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
                    <a class="back_to_overview js_backToOverview tooltip js_hideTipOnMobile right" href="javascript:void(0)" data-tooltip-title="{{ __('t_merchant.back') }}" style="display: inline;"></a>
                    <a class="small_back_to_overview js_backToOverview tooltip js_hideTipOnMobile" href="javascript:void(0)" data-tooltip-title="{{ __('t_merchant.back') }}"></a>
                </div>
                {{-- 4 hidden trader_link placeholders (OGame ufficiale 1:1) --}}
                <div id="js_traderResources" class="js_trader trader_link tooltipLeft js_hideTipOnMobile ipiHintable" data-ipi-hint="ipiTraderResources" data-tooltip-title="{{ __('t_merchant.exchange_resources_desc') }}" style="display: none;">
                    <h2>{{ __('t_merchant.resource_market') }}</h2>
                </div>
                <div id="js_traderAuctioneer" class="js_trader trader_link tooltipRight js_hideTipOnMobile ipiHintable" data-ipi-hint="ipiTraderAuctioneer" data-tooltip-title="{{ __('t_merchant.auctioneer_desc') }}" style="display: none;">
                    <h2>{{ __('t_merchant.auctioneer') }}</h2>
                </div>
                <div id="js_traderScrap" class="js_trader trader_link tooltipLeft js_hideTipOnMobile ipiHintable" data-ipi-hint="ipiTraderScrap" data-tooltip-title="{{ __('t_merchant.scrap_merchant_desc') }}" style="display: none;">
                    <h2>{{ __('t_merchant.scrap_merchant') }}</h2>
                </div>
                <div id="js_traderImportExport" class="js_trader trader_link tooltipRight js_hideTipOnMobile ipiHintable" data-ipi-hint="ipiTraderImportExport" data-tooltip-title="{{ __('t_merchant.import_export_desc') }}" style="display: none;">
                    <h2>{{ __('t_merchant.import_export') }}</h2>
                </div>
                @include('ingame.merchant._merchant-handlers')
            </div>

            <div class="c-right c-small"></div>

            <div id="div_traderAuctioneer" class="div_trader">
                    <div class="header">
                        <h2>{{ __('t_auctioneer.title') }}</h2>
                    </div>
                    <div class="content">
                        <p class="stimulus">
                            <a class="tooltipHTML help rules" title="{{ __('t_auctioneer.rules_title') }}|{!! __('t_auctioneer.rules_body') !!}"></a>
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
                                            } elseif ($lt === 'resource_boost') {
                                                $resKey = $p['resource'] ?? 'metal';
                                                $displayTitle = __('t_auctioneer.item_amplifier_' . $resKey) . ' ' . __('t_auctioneer.tier_' . $auction->tier->value);
                                            } elseif (str_starts_with($lt, 'booster_')) {
                                                $bn = ['booster_kraken' => 'KRAKEN', 'booster_newtron' => 'NEWTRON', 'booster_detroid' => 'DETROID'];
                                                $displayTitle = ($bn[$lt] ?? strtoupper(str_replace('booster_', '', $lt))) . ' ' . __('t_auctioneer.tier_' . $auction->tier->value);
                                            }

                                            // Format a reduction label (e.g. "-30m", "-2 ore", "-1 giorno")
                                            $fmtReduction = function (int $secs): string {
                                                if ($secs >= 86400) {
                                                    $d = intdiv($secs, 86400);
                                                    return '-' . $d . ' ' . ($d === 1 ? 'giorno' : 'giorni');
                                                }
                                                if ($secs >= 3600) {
                                                    $h = intdiv($secs, 3600);
                                                    return '-' . $h . ($h === 1 ? ' ora' : ' ore');
                                                }
                                                if ($secs >= 60) {
                                                    return '-' . intdiv($secs, 60) . 'm';
                                                }
                                                return '-' . $secs . 's';
                                            };

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
                                            } elseif ($lt === 'resource_boost') {
                                                $resKey = $p['resource'] ?? 'metal';
                                                $descr = __('t_auctioneer.amplifier_' . $resKey . '_desc', ['percent' => (int)($p['percent'] ?? 0)]);
                                            } elseif (str_starts_with($lt, 'booster_')) {
                                                $reductionLbl = !empty($p['duration_seconds']) ? $fmtReduction((int)$p['duration_seconds']) : '';
                                                $descr = __('t_auctioneer.' . $lt . '_desc', ['reduction' => $reductionLbl]);
                                            }

                                            // Duration: amplifiers show "1 settimana"; time-reduction boosters show "ora" (consumed instantly)
                                            $durLbl = '—';
                                            if ($lt === 'resource_boost') {
                                                $durLbl = __('t_auctioneer.duration_week');
                                            } elseif (str_starts_with($lt, 'booster_')) {
                                                $durLbl = __('t_auctioneer.tooltip_instant');
                                            } elseif (in_array($lt, ['resources', 'dark_matter', 'ship'])) {
                                                $durLbl = __('t_auctioneer.tooltip_instant');
                                            }

                                            // Build tooltip using the OGame JS format expected by generateHTML():
                                            // "TITLE|BODY" — title becomes <h1>, body is appended as raw HTML.
                                            // Double <br /><br /> = blank line between sections (matches official).
                                            // sanitizeTooltip() only strips <script>, so <br /> and spans pass through.
                                            // e() escapes user-provided text; structural HTML kept raw.
                                            // str_replace('"','&quot;') makes the value safe inside title="".
                                            $curInvKey = resolve(\OGame\Services\InventoryService::class)->registryKeyForLot($lt, $auction->tier->value, $p);
                                            $curInvCount = $curInvKey !== null ? (int) ($inventoryCounts[$curInvKey] ?? 0) : 0;
                                            $lotTipRaw =
                                                e($displayTitle)
                                                . '|'
                                                . e($descr)
                                                . '<br /><br />' . e(__('t_auctioneer.tooltip_duration')) . ': ' . e($durLbl)
                                                . '<br /><br />' . e(__('t_auctioneer.tooltip_price')) . ': &#8212;'
                                                . '<br />' . e(__('t_auctioneer.tooltip_inventory')) . ': ' . $curInvCount;
                                            $lotTip = str_replace(['"', "\n", "\r"], ['&quot;', '', ''], $lotTipRaw);

                                            // Deterministic ref for tooltip keying
                                            $lotRef = sha1($lt . '|' . $auction->lot_title);

                                            // Resolve the item-specific icon: items/{family}_{tier}.png.
                                            // Fallback order: exact tier -> any tier of same family -> random lot_*.png pool.
                                            $tierValue = $auction->tier->value;
                                            $familyKey = null;
                                            if ($lt === 'resource_boost') {
                                                $familyKey = 'amplifier_' . ($p['resource'] ?? 'metal');
                                            } elseif (str_starts_with($lt, 'booster_')) {
                                                $familyKey = str_replace('booster_', '', $lt);
                                            }

                                            if ($familyKey) {
                                                $exact = public_path('img/auctioneer/items/' . $familyKey . '_' . $tierValue . '.png');
                                                if (is_file($exact)) {
                                                    $lotSprite = '/img/auctioneer/items/' . $familyKey . '_' . $tierValue . '.png';
                                                } else {
                                                    // Family fallback: pick any available tier of same family
                                                    $familyMatches = glob(public_path('img/auctioneer/items/' . $familyKey . '_*.png')) ?: [];
                                                    if (!empty($familyMatches)) {
                                                        $lotSprite = '/img/auctioneer/items/' . basename($familyMatches[0]);
                                                    }
                                                }
                                            }

                                            if (!$lotSprite) {
                                                // Generic fallback for non-booster lot types or no icon found
                                                $spriteFiles = glob(public_path('img/auctioneer/lot_*.png')) ?: [];
                                                if (!empty($spriteFiles)) {
                                                    $idx = (int) hexdec(substr($lotRef, 0, 8)) % count($spriteFiles);
                                                    $lotSprite = '/img/auctioneer/' . basename($spriteFiles[$idx]);
                                                }
                                            }
                                        }
                                        $tierBorder = ['r_rare_140px' => '#FFD700', 'r_uncommon_140px' => '#C0C0C0', 'r_common_140px' => '#CD7F32', 'r_epic_140px' => '#B9F2FF'];
                                        $borderColor = $tierBorder[$tierLarge] ?? '#CD7F32';
                                        // Data for detail overlay (current auction)
                                        $curTitle = $hasAuction ? ($displayTitle ?? $auction->lot_title) : '';
                                        $curDescr = '';
                                        $curDescrExt = '';
                                        $curDur = $hasAuction ? ($durLbl ?? '') : '';
                                        $curDm = null;
                                        if ($hasAuction) {
                                            $lt2 = $auction->lot_type->value;
                                            $p2 = (array) $auction->lot_payload;
                                            if ($lt2 === 'resource_boost') {
                                                $rk = $p2['resource'] ?? 'metal';
                                                $curDescr = __('t_auctioneer.amplifier_' . $rk . '_desc', ['percent' => (int) ($p2['percent'] ?? 0)]);
                                                $curDescrExt = __('t_auctioneer.amplifier_' . $rk . '_desc_ext', ['percent' => (int) ($p2['percent'] ?? 0)]);
                                            } elseif (str_starts_with($lt2, 'booster_')) {
                                                $sec2 = (int) ($p2['duration_seconds'] ?? 0);
                                                $red2 = $sec2 >= 86400 ? '-' . intdiv($sec2, 86400) . ' ' . (intdiv($sec2, 86400) === 1 ? 'giorno' : 'giorni')
                                                    : ($sec2 >= 3600 ? '-' . intdiv($sec2, 3600) . (intdiv($sec2, 3600) === 1 ? ' ora' : ' ore')
                                                    : ($sec2 >= 60 ? '-' . intdiv($sec2, 60) . 'm' : '-' . $sec2 . 's'));
                                                $curDescr = __('t_auctioneer.' . $lt2 . '_desc', ['reduction' => $red2]);
                                                $curDescrExt = __('t_auctioneer.' . $lt2 . '_desc_ext');
                                            }
                                            $curDm = $dmPriceMap[$lt2 . '|' . $auction->tier->value] ?? null;
                                        }
                                    @endphp
                                    @php
                                        $tierFrameOffsets = ['r_rare_140px' => '0px 0px', 'r_uncommon_140px' => '-141px 0px', 'r_common_140px' => '-282px 0px', 'r_epic_140px' => '-564px 0px'];
                                        $framePos = $tierFrameOffsets[$tierLarge] ?? '-282px 0px';
                                    @endphp
                                    <a class="detail_button tooltipHTML js_hideTipOnMobile js_currentSlideIn {{ $tierLarge }}"
                                       href="javascript:void(0);"
                                       style="position:relative;display:block;width:140px;height:140px;background:#000;box-sizing:border-box;"
                                       @if ($hasAuction)
                                           ref="{{ $lotRef }}"
                                           title="{!! $lotTip !!}"
                                           data-lot-title="{{ $curTitle }}"
                                           data-lot-type="{{ $auction->lot_type->value }}"
                                           data-tier="{{ $auction->tier->value }}"
                                           data-rarity="{{ $tier }}"
                                           data-lot-image="{{ $lotSprite }}"
                                           data-description="{{ $curDescr }}"
                                           data-description-ext="{{ $curDescrExt !== '' ? $curDescrExt : $curDescr }}"
                                           data-duration="{{ $curDur }}"
                                           data-inventory-count="{{ $curInvCount ?? 0 }}"
                                           @if ($curDm !== null) data-dm-price="{{ $curDm }}" @endif
                                       @else title="" @endif>
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
                                            $hDm = $dmPriceMap[$h['lot_type'] . '|' . $h['tier']] ?? null;
                                            // Build description + duration from lot_type + payload (mirror live auction tooltip logic)
                                            $hP = (array) ($h['lot_payload'] ?? []);
                                            $hDescr = '';
                                            $hDescrExt = '';
                                            $hDur = __('t_auctioneer.tooltip_instant');
                                            if ($h['lot_type'] === 'resource_boost') {
                                                $rk = $hP['resource'] ?? 'metal';
                                                $hDescr = __('t_auctioneer.amplifier_' . $rk . '_desc', ['percent' => (int) ($hP['percent'] ?? 0)]);
                                                $hDescrExt = __('t_auctioneer.amplifier_' . $rk . '_desc_ext', ['percent' => (int) ($hP['percent'] ?? 0)]);
                                                $hDur = __('t_auctioneer.duration_week');
                                            } elseif (str_starts_with($h['lot_type'], 'booster_')) {
                                                $sec = (int) ($hP['duration_seconds'] ?? 0);
                                                $red = $sec >= 86400 ? '-' . intdiv($sec, 86400) . ' ' . (intdiv($sec, 86400) === 1 ? 'giorno' : 'giorni')
                                                    : ($sec >= 3600 ? '-' . intdiv($sec, 3600) . (intdiv($sec, 3600) === 1 ? ' ora' : ' ore')
                                                    : ($sec >= 60 ? '-' . intdiv($sec, 60) . 'm' : '-' . $sec . 's'));
                                                $hDescr = __('t_auctioneer.' . $h['lot_type'] . '_desc', ['reduction' => $red]);
                                                $hDescrExt = __('t_auctioneer.' . $h['lot_type'] . '_desc_ext');
                                            }
                                            // Italian title from payload (amplifiers + boosters) else fallback to stored lot_title
                                            $hTitle = $h['lot_title'];
                                            if ($h['lot_type'] === 'resource_boost') {
                                                $rk = $hP['resource'] ?? 'metal';
                                                $hTitle = __('t_auctioneer.item_amplifier_' . $rk) . ' ' . __('t_auctioneer.tier_' . $h['tier']);
                                            } elseif (str_starts_with($h['lot_type'], 'booster_')) {
                                                $bn = ['booster_kraken' => 'KRAKEN', 'booster_newtron' => 'NEWTRON', 'booster_detroid' => 'DETROID'];
                                                $hTitle = ($bn[$h['lot_type']] ?? strtoupper(str_replace('booster_', '', $h['lot_type']))) . ' ' . __('t_auctioneer.tier_' . $h['tier']);
                                            }
                                            // Hover tooltip in TITLE|BODY format (tipped.js via generateHTML)
                                            $hPriceLbl = $hDm !== null ? number_format((int) $hDm, 0, ',', '.') . ' ' . __('t_auctioneer.dark_matter') : '&#8212;';
                                            $hInvKey = resolve(\OGame\Services\InventoryService::class)->registryKeyForLot($h['lot_type'], $h['tier'], $hP);
                                            $hInvCount = $hInvKey !== null ? (int) ($inventoryCounts[$hInvKey] ?? 0) : 0;
                                            $hTipRaw = e($hTitle) . '|'
                                                . e($hDescr)
                                                . '<br /><br />' . e(__('t_auctioneer.tooltip_duration')) . ': ' . e($hDur)
                                                . '<br /><br />' . e(__('t_auctioneer.tooltip_price')) . ': ' . $hPriceLbl
                                                . '<br />' . e(__('t_auctioneer.tooltip_inventory')) . ': ' . $hInvCount;
                                            $hTip = str_replace(['"', "\n", "\r"], ['&quot;', '', ''], $hTipRaw);
                                            $galaxyHref = ($h['sold'] && $h['winner_galaxy'] !== null && $h['winner_system'] !== null)
                                                ? route('galaxy.index', ['galaxy' => $h['winner_galaxy'], 'system' => $h['winner_system']])
                                                : null;
                                            // Resolve 140px sprite for slideIn preview (same logic as current auction)
                                            $hFamily = null;
                                            $hLt = $h['lot_type'];
                                            $hPayload = (array) ($h['lot_payload'] ?? []);
                                            if ($hLt === 'resource_boost') {
                                                $hFamily = 'amplifier_' . ($hPayload['resource'] ?? 'metal');
                                            } elseif (str_starts_with($hLt, 'booster_')) {
                                                $hFamily = str_replace('booster_', '', $hLt);
                                            }
                                            $hLargeSprite = null;
                                            if ($hFamily) {
                                                $exactH = public_path('img/auctioneer/items/' . $hFamily . '_' . $h['tier'] . '.png');
                                                if (is_file($exactH)) {
                                                    $hLargeSprite = '/img/auctioneer/items/' . $hFamily . '_' . $h['tier'] . '.png';
                                                } else {
                                                    $famM = glob(public_path('img/auctioneer/items/' . $hFamily . '_*.png')) ?: [];
                                                    if (!empty($famM)) $hLargeSprite = '/img/auctioneer/items/' . basename($famM[0]);
                                                }
                                            }
                                            if (!$hLargeSprite) {
                                                $pool = glob(public_path('img/auctioneer/lot_*.png')) ?: [];
                                                if (!empty($pool)) {
                                                    $idx = (int) hexdec(substr(sha1($hLt . '|' . $h['lot_title']), 0, 8)) % count($pool);
                                                    $hLargeSprite = '/img/auctioneer/' . basename($pool[$idx]);
                                                }
                                            }
                                        @endphp
                                        <li class="{{ $parity }}{{ $moreClass }}" @if ($i >= 3) style="display:none" @endif>
                                            <a class="js_historySlideIn"
                                               href="javascript:void(0);"
                                               data-lot-title="{{ $hTitle }}"
                                               data-lot-type="{{ $h['lot_type'] }}"
                                               data-tier="{{ $h['tier'] }}"
                                               data-rarity="{{ $rClass }}"
                                               data-lot-image="{{ $hLargeSprite ?? $h['lot_image'] }}"
                                               data-description="{{ $hDescr }}"
                                               data-description-ext="{{ $hDescrExt !== '' ? $hDescrExt : $hDescr }}"
                                               data-duration="{{ $hDur }}"
                                               data-inventory-count="{{ $hInvCount }}"
                                               @if ($hDm !== null) data-dm-price="{{ $hDm }}" @endif
                                               title="{{ $hTitle }}">
                                                <img height="30" width="30" alt="" src="{{ $hLargeSprite ?? $h['lot_image'] }}" class="item_img tooltipHTML tooltipLeft {{ $rClass }}" title="{!! $hTip !!}">
                                            </a>
                                            @if ($h['sold'])
                                                <span class="detail sum">{{ number_format($h['winning_bid'], 0, ',', '.') }}</span>
                                                <span class="detail player">
                                                    @if ($galaxyHref)
                                                        <a href="{{ $galaxyHref }}">{{ $h['winner_name'] }}</a>
                                                    @else
                                                        <a>{{ $h['winner_name'] }}</a>
                                                    @endif
                                                </span>
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
            const prev = parseInt(countdownEl?.dataset.endTimestamp || '0', 10);
            const ne = parseInt(d.ends_at || d.waiting_ends_at || 0, 10);
            // Reload only when both timestamps are valid AND meaningfully different (>1s).
            // Guards against null/undefined AJAX fields, transient Running↔Waiting blips,
            // and timezone/rounding noise that previously caused infinite reload loops.
            if (countdownEl && prev > 0 && ne > 0 && Math.abs(ne - prev) > 1) { location.reload(); return; }
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

    // History slideIn: click a past lot → render #itemDetails overlay (official OGame structure)
    (function () {
        const detailEl = document.getElementById('detail');
        if (!detailEl) return;

        const T_BUY_AT_COST = @json(__('t_auctioneer.buy_at_cost'));
        const T_BUY_AND_ACTIVATE = @json(__('t_auctioneer.buy_and_activate'));
        const T_ACTIVATE = @json(__('t_auctioneer.buy_activate_short'));
        const T_DM_SHORT = @json(__('t_auctioneer.dm_short'));
        const T_INVENTORY = @json(__('t_auctioneer.tooltip_inventory'));
        const T_DURATION = @json(__('t_auctioneer.tooltip_duration'));
        const T_NOT_AVAIL = @json(__('t_auctioneer.not_available_yet'));
        const T_RULES_TITLE = @json(__('t_auctioneer.rules_title'));
        const T_RULES_BODY = @json(__('t_auctioneer.rules_body'));

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function render(el) {
            const title = el.dataset.lotTitle || '';
            const img = el.dataset.lotImage || '';
            const descr = el.dataset.description || '';
            const descrExt = el.dataset.descriptionExt || descr;
            const duration = el.dataset.duration || '';
            const dmPrice = el.dataset.dmPrice || null;
            const priceLabel = dmPrice ? Number(dmPrice).toLocaleString('it-IT') : '---';
            const rulesTooltip = esc(T_RULES_TITLE) + '|' + T_RULES_BODY.replace(/"/g, '&quot;');

            // Structure mirrors official OGame auctioneer item detail overlay
            detailEl.innerHTML = `
                <div id="itemDetails">
                    <div class="detailsHolder">
                        <div id="pic"><img src="${esc(img)}" alt="${esc(title)}"></div>
                        <div id="content">
                            <h2>${esc(title)}</h2>
                            <span class="inventoryAmount">${esc(T_INVENTORY)}: <span class="amount">${esc(el.dataset.inventoryCount || '0')}</span></span>
                            <a class="close_details" id="close" href="javascript:void(0);"></a>
                            <br class="clearfloat">
                            <div id="wrapper">
                                <div id="features">
                                    <p class="extended_description">${esc(descrExt)} <span class="more_info blue_txt bold">${esc(T_DURATION)}: ${esc(duration)}</span></p>
                                    <a class="build-it_disabled item tooltip js_hideTipOnMobile" title="${esc(T_NOT_AVAIL)}">
                                        <span>${esc(T_BUY_AT_COST)} ${priceLabel} ${esc(T_DM_SHORT)}</span>
                                    </a>
                                    <a class="build-it_disabled buyAndActivate tooltip js_hideTipOnMobile" title="${esc(T_NOT_AVAIL)}">
                                        <span>${esc(T_BUY_AND_ACTIVATE)}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="description">
                        <a class="tooltipHTML help" title="${rulesTooltip}"></a>
                        <p><span>${esc(descr)}</span></p>
                    </div>
                </div>`;
            detailEl.classList.add('active');
            // Wire close button
            const closeBtn = detailEl.querySelector('.close_details');
            if (closeBtn) closeBtn.addEventListener('click', hide);
            // Suppress clicks on disabled buttons
            detailEl.querySelectorAll('a.build-it_disabled').forEach(b => {
                b.addEventListener('click', e => e.preventDefault());
            });
        }

        function hide() {
            detailEl.classList.remove('active');
            detailEl.innerHTML = '<div id="techDetailLoading"></div>';
        }

        document.querySelectorAll('.js_historySlideIn, .js_currentSlideIn').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                render(this);
            }, true); // capture phase — runs before jQuery delegated handlers on document
        });
    })();

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
