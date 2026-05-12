
@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <!-- JAVASCRIPT -->
    <script type="text/javascript">
        var textContent = [];
        textContent[0] = "{{ __('t_ingame.overview.diameter') }}:";
        textContent[1] = "{!! $planet_diameter !!}km (<span>{{ $building_count }}<\/span>\/<span>{{ $max_building_count }}<\/span>)";
        textContent[2] = "{{ __('t_ingame.overview.temperature') }}:";
        textContent[3] = "{!! $planet_temp_min !!}\u00b0C to {!! $planet_temp_max !!}\u00b0C";
        textContent[4] = "{{ __('t_ingame.overview.position') }}:";
        textContent[5] = "<a  href=\"{{ route('galaxy.index', ['galaxy' => 4, 'system' => 4, 'position' => 4])  }}\" >[{!! $planet_coordinates !!}]<\/a>";
        textContent[6] = "{{ __('t_ingame.overview.points') }}:";
        textContent[7] = "<a href='{{ route('highscore.index')  }}'>{{ $user_points }} ({{ __('t_ingame.overview.score_place') }} {!! $user_rank !!} {{ __('t_ingame.overview.score_of') }} {!! $max_rank !!})<\/a>";
        textContent[8] = "{{ __('t_ingame.overview.honour_points') }}:";
        textContent[9] = "0";

        var textDestination = [];
        textDestination[0] = "diameterField";
        textDestination[1] = "diameterContentField";
        textDestination[2] = "temperatureField";
        textDestination[3] = "temperatureContentField";
        textDestination[4] = "positionField";
        textDestination[5] = "positionContentField";
        textDestination[6] = "scoreField";
        textDestination[7] = "scoreContentField";
        textDestination[8] = "honorField";
        textDestination[9] = "honorContentField";
        var currentIndex = 0;
        var currentChar = 0;
        var linetwo = 0;

        @if($planet_move)
        var planetMoveLoca = {
            "askTitle": "{{ __('t_ingame.planet_move.resettle_title') }}",
            "askCancel": "{{ __('t_ingame.planet_move.cancel_confirm') }}",
            "yes": "{{ __('t_ingame.shared.yes') }}",
            "no": "{{ __('t_ingame.shared.no') }}",
            "success": "{{ __('t_ingame.planet_move.cancel_success') }}",
            "error": "{{ __('t_ingame.shared.error') }}"
        };
        var planetMoveCooldown = {{ $planet_move_countdown }};
        new SimpleCountdownTimer('#moveCountdown', {{ $planet_move_countdown }}, '{{ route('overview.index') }}');
        @elseif($planet_move_cooldown > 0)
        new SimpleCountdownTimer('#moveCountdown', {{ $planet_move_cooldown }}, '{{ route('overview.index') }}');
        @endif

        var cancelProduction_id;
        var production_listid;

        function cancelProduction(id, listid, question) {
            cancelProduction_id = id;
            production_listid = listid;
            errorBoxDecision("{{ __('t_ingame.shared.caution') }}", "" + question + "", "{{ __('t_ingame.shared.yes') }}", "{{ __('t_ingame.shared.no') }}", cancelProductionStart);
        }

        function cancelProductionStart() {
            $('<form id="cancelProductionStart" action="{{ route('resources.cancelbuildrequest') }}" method="POST" style="display: none;">{{ csrf_field() }}<input type="hidden" name="building_id" value="' + cancelProduction_id + '" /> <input type="hidden" name="building_queue_id" value="' + production_listid + '" /> <input type="hidden" name="redirect" value="overview" /></form>').appendTo('body').submit();
        }

        function cancelResearch(id, listid, question) {
            cancelProduction_id = id;
            production_listid = listid;
            errorBoxDecision("{{ __('t_ingame.shared.caution') }}", "" + question + "", "{{ __('t_ingame.shared.yes') }}", "{{ __('t_ingame.shared.no') }}", cancelResearchStart);
        }

        function cancelResearchStart() {
            $('<form id="cancelProductionStart" action="{{ route('research.cancelbuildrequest') }}" method="POST" style="display: none;">{{ csrf_field() }}<input type="hidden" name="building_id" value="' + cancelProduction_id + '" /> <input type="hidden" name="building_queue_id" value="' + production_listid + '" /> <input type="hidden" name="redirect" value="overview" /></form>').appendTo('body').submit();
        }

        function initType() {
            type();
        }

        $(document).ready(function () {
            gfSlider = new GFSlider(getElementByIdWithCache('detailWrapper'));
            initType();
            @if (!empty($ship_active))
            // Countdown for inline ship element (pusher)
            new shipCountdown(getElementByIdWithCache('shipAllCountdown'), getElementByIdWithCache('shipCountdown'), getElementByIdWithCache('shipSumCount'), {{ $ship_active->time_countdown }}, {{ $ship_active->time_countdown_object_next }}, {{ $ship_queue_time_countdown }}, {{ $ship_active->object_amount_remaining }}, "{{ route('shipyard.index') }}");
            @endif
        });
    </script>

    <div id="eventboxContent" style="display: none">
        <img height="16" width="16" src="/img/icons/3f9884806436537bdec305aa26fc60.gif"/>
    </div>

  
    <div id="inhalt">
        <div id="planet" style="background-image:url({{ asset('img/headers/overview/' . $header_filename) }}.jpg);">

            <div id="detailWrapper">
                @if ($has_moon)
                    <div id="moon">
                        <a href="{{ request()->url() }}?{{ http_build_query([...request()->query(), 'cp' => $other_planet->getPlanetId()]) }}"
                           class="tooltipBottom js_hideTipOnMobile"
                           title="{{ __('t_ingame.overview.switch_to_moon') }} {{ $other_planet->getPlanetName() }}">
                            <img alt="{{ $other_planet->getPlanetName() }}" src="{!! asset('img/moons/big/' . $other_planet->getPlanetImageType() . '.gif') !!}">
                        </a>
                    </div>
                @elseif ($has_planet)
                    <div id="planet_as_moon">
                        <a href="{{ request()->url() }}?{{ http_build_query([...request()->query(), 'cp' => $other_planet->getPlanetId()]) }}"
                           class="tooltipBottom js_hideTipOnMobile"
                           title="{{ __('t_ingame.overview.switch_to_planet') }} {{ $other_planet->getPlanetName() }}">
                            <img alt="{{ $other_planet->getPlanetName() }}" src="{!! asset('img/planets/' . $other_planet->getPlanetBiomeType() . '_moon_view.jpg') !!}">
                        </a>
                    </div>
                @endif

                <div id="header_text">
                    <h2>
                        <a href="javascript:void(0);" class="openPlanetRenameGiveupBox">

                            <p class="planetNameOverview">{{ __('t_ingame.overview.page_title') }} -</p>
                            <span id="planetNameHeader">
                            {{ $planet_name }}
                        </span>
                            <img class="hinted tooltip" title="{{ __('t_ingame.overview.abandon_rename_title') }}"
                                 src="/img/icons/1f57d944fff38ee51d49c027f574ef.gif" width="16" height="16"/>
                        </a>
                    </h2>
                </div>
                <div id="detail" class="detail_screen">
                    <div id="techDetailLoading"></div>
                </div>
                <div id="planetdata">
                    <div class="overlay"></div>
                    <div id="planetDetails">
                        <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td class="desc">
                                    <span id="diameterField"></span>
                                </td>
                                <td class="data">
                                    <span id="diameterContentField"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="desc">
                                    <span id="temperatureField"></span>
                                </td>
                                <td class="data">
                                    <span id="temperatureContentField"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="desc">
                                    <span id="positionField"></span>
                                </td>
                                <td class="data">
                                    <span id="positionContentField"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="desc">
                                    <span id="scoreField"></span></td>
                                <td class="data">
                                    <span id="scoreContentField"></span>
                                </td>
                            </tr>

                            <tr>
                                <td class="desc">
                                    <span id="honorField"></span></td>
                                <td class="data ">
                                    <span id="honorContentField"></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="planetOptions">

                        <div class="planetMoveStart fleft" style="display: inline;">
                            @if($planet_move)
                                @php
                                    $t = $planet_move_countdown;
                                    $countdownParts = [];
                                    foreach (['d' => 86400, 'h' => 3600, 'm' => 60, 's' => 1] as $u => $v) {
                                        $n = intdiv($t, $v);
                                        if ($n > 0) {
                                            $t -= $n * $v;
                                            $countdownParts[] = $n . $u;
                                        }
                                        if (count($countdownParts) >= 2) break;
                                    }
                                    $countdownFormatted = implode(' ', $countdownParts);
                                @endphp
                                <span class="planetMoveProgress fleft">
                                    @if(count($planet_move_blockers) > 0)
                                        <span class="tooltip planetMoveIcons planetMoveBreakup icon"
                                              title="{{ __('t_ingame.planet_move.blockers_title') }} {{ implode(', ', $planet_move_blockers) }}"></span>
                                    @else
                                        <span class="tooltip planetMoveIcons planetMoveOk icon"
                                              title="{{ __('t_ingame.planet_move.no_blockers') }}"></span>
                                    @endif
                                    <span id="moveProgress">
                                        <a id="moveCountdown" class="tooltip js_hideTipOnMobile undermark"
                                           href="{{ route('galaxy.index', ['galaxy' => $planet_move->target_galaxy, 'system' => $planet_move->target_system]) }}"
                                           title="[{{ $planet_move_target }}]">{{ $countdownFormatted }}
                                        </a>
                                        <a class="tooltip js_hideTipOnMobile cancelMove icon_link"
                                           href="javascript:void(0);"
                                           rel="{{ route('planetMove.cancel') }}"
                                           title="{{ __('t_ingame.planet_move.cancel') }}">
                                            <span class="icon icon_quit"></span>
                                        </a>
                                    </span>
                                </span>
                            @elseif($planet_move_cooldown > 0)
                                @php
                                    $t = $planet_move_cooldown;
                                    $cooldownParts = [];
                                    foreach (['d' => 86400, 'h' => 3600, 'm' => 60, 's' => 1] as $u => $v) {
                                        $n = intdiv($t, $v);
                                        if ($n > 0) {
                                            $t -= $n * $v;
                                            $cooldownParts[] = $n . $u;
                                        }
                                        if (count($cooldownParts) >= 2) break;
                                    }
                                    $cooldownFormatted = implode(' ', $cooldownParts);
                                @endphp
                                <span class="tooltip planetMoveIcons planetMoveInactive icon"
                                      title="{{ __('t_ingame.planet_move.cooldown_title') }}"></span>
                                <span id="moveCountdown" class="status_abbr_longinactive tooltip fleft"
                                      title="{{ __('t_ingame.planet_move.cooldown_title') }}">{{ $cooldownFormatted }}</span>
                            @else
                                <a class="tooltipLeft dark_highlight_tablet fleft"
                                   href='{{ route('galaxy.index') }}'
                                   title="{{ __('t_ingame.planet_move.explanation') }}"
                                   data-tooltip-button="{{ __('t_ingame.planet_move.to_galaxy') }}">
                                    <span class="planetMoveIcons settings planetMoveDefault icon fleft"></span>
                                    <span class="planetMoveOverviewMoveLink">{{ __('t_ingame.planet_move.relocate') }}</span>
                                </a>
                            @endif
                        </div>

                        <a class="dark_highlight_tablet float_right openPlanetRenameGiveupBox"
                           href="javascript:void(0);">
                            <span class="planetMoveOverviewGivUpLink">{{ __('t_ingame.overview.abandon_rename') }}</span>
                            <span class="planetMoveIcons settings planetMoveGiveUp icon"></span>
                        </a>
                    </div>
                </div>
            </div>

            <div id="buffBar" class="sliderWrapper">
                <div data-uuid="" data-id="" class="add_item">
                    <a class="activate_item border3px" href="javascript:void(0);" ref="1"></a>
                </div>

                <ul class="active_items hidden">
                    <li>
                    </li>
                </ul>
            </div>


        </div>
        <div class="c-left"></div>
        <div class="c-right"></div>

        <div id="productionboxBottom">
            <div class="productionBoxBuildings boxColumn building">
                <div id="productionboxbuildingcomponent" class="productionboxbuilding injectedComponent parent overview">
                    <div class="content-box-s">
                        <div class="header">
                            <h3>{{ __('t_ingame.overview.buildings') }}</h3>
                        </div>
                        <div class="content">
                            <table cellpadding="0" cellspacing="0" class="construction active">
                                <tbody>
                                {{-- Building is actively being built. --}}
                                @include ('ingame.shared.buildqueue.building-active', ['build_active' => $build_active])
                                {{-- Building queue has items. --}}
                                @include ('ingame.shared.buildqueue.building-queue', ['build_queue' => $build_queue])
                                </tbody>
                            </table>
                        </div>
                        <div class="footer"></div>
                    </div>
                </div>
                <!--<div id="productionboxlfbuildingcomponent" class="productionboxlfbuilding injectedComponent parent overview"><div class="content-box-s">
                        <div class="header">
                            <h3>Lifeform Buildings
                            </h3>
                        </div>
                        <div class="content">
                            <table cellspacing="0" cellpadding="0" class="construction active">
                                <tbody>
                                <tr>
                                    <td colspan="2" class="idle">
                                        <a class="tooltip js_hideTipOnMobile " title="The lifeforms are not currently constructing any buildings. Click here to view buildings.
" href="#TODO_=ingame&amp;component=lfbuildings">
                                            No buildings in construction.
                                            <br>
                                            (View Buildings
                                            )
                                        </a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="footer"></div>
                    </div>
                    <script type="text/javascript">
                        var scheduleBuildListEntryUrl = '#TODOpage=componentOnly&component=buildlistactions&action=scheduleEntry&asJson=1';
                        var LOCA_ERROR_INQUIRY_NOT_WORKED_TRYAGAIN = 'Your last action could not be processed. Please try again.';
                        redirectPremiumLink = '#TODOpage=premium&showDarkMatter=1'
                    </script>
                </div>
            </div>-->
            </div>
            <div class="productionBoxResearch boxColumn research">
                <div id="productionboxresearchcomponent" class="productionboxresearch injectedComponent parent overview">
                    <div class="content-box-s">
                        <div class="header"><h3>{{ __('t_ingame.overview.research') }}</h3></div>
                        <div class="content">
                            {{-- Building is actively being built. --}}
                            @include ('ingame.shared.buildqueue.research-active', ['build_active' => $research_active])
                            {{-- Building queue has items. --}}
                            @include ('ingame.shared.buildqueue.research-queue', ['build_queue' => $research_queue])
                        </div>
                        <div class="footer"></div>
                    </div>
                </div>
                <!--<div id="productionboxlfresearchcomponent" class="productionboxlfresearch injectedComponent parent overview"><div class="content-box-s">
                        <div class="header">
                            <h3>Lifeform Research
                            </h3>
                        </div>
                        <div class="content">
                            <table cellspacing="0" cellpadding="0" class="construction active">
                                <tbody>
                                <tr>
                                    <td colspan="2" class="idle">
                                        <a class="tooltip js_hideTipOnMobile " title="There is currently no research in progress. Click here to view lifeform techs.
" href="#TODOpage=ingame&amp;component=lfresearch">
                                            There is no research in progress at the moment.
                                            <br>
                                            (View Lifeform Development
                                            )
                                        </a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="footer"></div>
                    </div>-->
                <script type="text/javascript">
                    var scheduleBuildListEntryUrl = '#TODOpage=componentOnly&component=buildlistactions&action=scheduleEntry&asJson=1';
                    var LOCA_ERROR_INQUIRY_NOT_WORKED_TRYAGAIN = 'Your last action could not be processed. Please try again.';
                    redirectPremiumLink = '#TODOpage=premium&showDarkMatter=1'
                </script>
            </div>

            <div class="productionBoxShips boxColumn ship">

                <div id="productionboxshipyardcomponent" class="productionboxshipyard injectedComponent parent overview">
                    <div class="content-box-s">
                        <div class="header"><h3>{{ __('t_resources.shipyard.title') }}</h3></div>
                        <div class="content">
                            {{-- Building is actively being built. --}}
                            @include ('ingame.shared.buildqueue.unit-active', ['build_active' => $ship_active, 'build_queue_countdown' => $ship_queue_time_countdown])
                            {{-- Building queue has items. --}}
                            @include ('ingame.shared.buildqueue.unit-queue', ['build_queue' => $ship_queue])
                        </div>
                        <div class="footer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inventory activation overlay: replica 1:1 del native (dati DevTools 24-04-2026) --}}
    <style>
        /* === Card root === */
        #invOverlayRoot{position:absolute;z-index:900;display:none;width:654px;height:258px;font:12px/12px Verdana,Arial,sans-serif;}
        #invOverlayRoot[data-open="1"]{display:block;}
        #invOverlayRoot #invOverlayDetail{display:block;width:654px;height:258px;}

        /* === Container principale === */
        #invOverlayRoot #activeBuffDetails{position:relative;width:654px;height:258px;background:#0d1014 url(//gf3.geo.gfsrv.net/cdnb2/862bd57e5d13bfc8cd9ce0a1d72c9f.png) 0 0 no-repeat;color:#f1f1f1;z-index:11;overflow:hidden;}
        #invOverlayRoot #activeBuffDetails h2.detail_screen_h2{margin:0;padding:3px 0 0 40px;color:#d29d00;font:700 12px/16px Verdana,Arial,Helvetica,sans-serif;}
        #invOverlayRoot .close_details{position:absolute;top:4px;right:8px;width:16px;height:16px;cursor:pointer;z-index:20;text-decoration:none;}

        /* === Slider area (410×225, float left) === */
        #invOverlayRoot .active_item_slider{position:relative;top:12px;left:10px;width:410px;height:225px;float:left;text-align:center;}
        #invOverlayRoot .anythingSlider{position:relative;width:350px;height:200px;margin:0 auto;padding:0;overflow:visible;}
        /* Frecce laterali slider (sprite nativo Gameforge) */
        #invOverlayRoot .anythingSlider .arrow{background:none;top:50%;position:absolute;display:block;z-index:30;}
        #invOverlayRoot .anythingSlider .arrow a{display:block;height:135px;width:25px;margin:-85px 0 0;background:url(/img/inventory/buff_sprite.png) no-repeat;text-align:center;outline:0;cursor:pointer;}
        #invOverlayRoot .anythingSlider .arrow a span{display:block;text-indent:-9999px;}
        #invOverlayRoot .anythingSlider .arrow.back{left:-28px;}
        #invOverlayRoot .anythingSlider .arrow.forward{right:-28px;}
        /* Native OGame positions (verified live computed style):
           back default -60 -310, forward default -120 -310,
           hover shifts to adjacent cell (back -30, forward -150). */
        #invOverlayRoot .anythingSlider .arrow.back a{background-position:-60px -310px;}
        #invOverlayRoot .anythingSlider .arrow.forward a{background-position:-120px -310px;}
        #invOverlayRoot .anythingSlider .arrow.back a:hover{background-position:-30px -310px;}
        #invOverlayRoot .anythingSlider .arrow.forward a:hover{background-position:-150px -310px;}
        #invOverlayRoot .anythingWindow{width:100%;height:100%;overflow:hidden;position:relative;}
        #invOverlayRoot ul.anythingBase{list-style:none;margin:0;padding:0;position:relative;width:2800px;}
        #invOverlayRoot ul.anythingBase > li.panel{width:350px;height:200px;float:left;display:block;padding:0;margin:0;list-style:none;}
        #invOverlayRoot ul.anythingBase > li.panel:not(.activePage){display:none;}

        /* === Tile item (75×90, 4 per riga) === */
        #invOverlayRoot .active_item_slider .item_img{position:relative;display:block;float:left;width:75px;height:90px;margin:7px 0 0 10px;background-repeat:no-repeat;background-size:71px 71px;background-position:2px 2px;cursor:pointer;}
        #invOverlayRoot .active_item_slider .item_img .item_img_box{position:relative;width:75px;height:75px;margin:0 0 2px;background:url(//gf1.geo.gfsrv.net/cdn9b/14e5b9c95e1eefb2700775852cdb47.png) -150px 0 no-repeat;z-index:10;}
        #invOverlayRoot .active_item_slider .item_img.r_common .item_img_box{background-position:-150px 0;}
        #invOverlayRoot .active_item_slider .item_img.r_uncommon .item_img_box{background-position:-75px 0;}
        #invOverlayRoot .active_item_slider .item_img.r_rare .item_img_box{background-position:0 0;}
        #invOverlayRoot .active_item_slider .item_img.r_epic .item_img_box{background-position:-300px 0;}
        #invOverlayRoot .active_item_slider .item_img.r_buddy .item_img_box{background-position:-225px 0;}
        #invOverlayRoot .active_item_slider .item_img a.detail_button{position:relative;display:block;width:75px;height:75px;text-decoration:none;text-indent:-9999px;color:#ff9600;}
        #invOverlayRoot .active_item_slider .item_img a.detail_button.active,
        #invOverlayRoot .active_item_slider .item_img.selected a.detail_button,
        #invOverlayRoot .active_item_slider .item_img:hover a.detail_button{background:url(//gf1.geo.gfsrv.net/cdnff/ce5a256cdc38d913295a8cdecbad41.png) no-repeat;}
        #invOverlayRoot .active_item_slider .item_img .activation{position:absolute;top:0;left:0;width:100%;height:0;background:#000;opacity:.6;z-index:1;border-top-left-radius:3px;border-top-right-radius:3px;}
        #invOverlayRoot .active_item_slider .item_img .ecke{position:relative;display:block;float:left;top:60px;left:0;width:72px;text-align:right;color:#ff9600;font:11px/11px Verdana,Arial,sans-serif;text-indent:0;}
        #invOverlayRoot .active_item_slider .item_img .level.amount{display:inline;color:#ff9600;}

        /* === Paginator (in fondo alla slider area) === */
        #invOverlayRoot .buffPaginator{position:absolute;left:10px;bottom:6px;width:410px;min-height:22px;display:flex;flex-wrap:nowrap;align-items:center;justify-content:flex-start;gap:3px;color:#f1f1f1;font:11px/11px Verdana,Arial,sans-serif;z-index:15;}
        #invOverlayRoot .buffPaginator #buffPages{display:flex;align-items:center;}
        #invOverlayRoot .buffPaginator #buffPages ul.panelBuff{display:flex;align-items:center;margin:0;padding:0;list-style:none;}
        #invOverlayRoot .buffPaginator .arrowPrev,
        #invOverlayRoot .buffPaginator .arrowNext{border:none;background:#34414f;color:#848484;display:flex;text-decoration:none;height:20px;width:0;margin:5px 3px;border-radius:5px;justify-content:center;padding:1.3px 8px 1px 8px;cursor:pointer;font:inherit;}
        #invOverlayRoot .buffPaginator ul.panelBuff{display:flex;list-style:none;margin:0;padding:0;gap:2px;}
        #invOverlayRoot .buffPaginator ul.panelBuff li{display:inline-block;}
        #invOverlayRoot .buffPaginator ul.panelBuff li button{background:rgba(0,0,0,.4);color:#ccc;padding:2px 6px;border:0;cursor:pointer;font:inherit;min-width:18px;}
        #invOverlayRoot .buffPaginator ul.panelBuff li button.activePager{background:#465059;color:#fff;}

        /* === Right detail panel (197×209, margin-left 425) === */
        #invOverlayRoot .active_item_details{position:relative;width:197px;height:209px;margin:11px 0 0 425px;padding:5px 7px;background:linear-gradient(to bottom,#1c242d 0%,#10181e 100%);border-radius:5px;box-sizing:content-box;}
        #invOverlayRoot #itemDetailBox{width:197px;height:156px;color:#f1f1f1;font:12px/12px Verdana,Arial,sans-serif;}
        #invOverlayRoot .js_itemName{margin:0;padding:0;color:#d29d00;font:700 12px/16px Verdana,Arial,Helvetica,sans-serif;}
        #invOverlayRoot .js_itemEffect{margin:0;padding:0 0 5px;color:#f1f1f1;font:9px/13px Verdana,Arial,sans-serif;}
        #invOverlayRoot .active_item_details .blue_txt{margin:0;padding:0 0 5px;color:#6f9fc8;font:700 9px/13px Verdana,Arial,sans-serif;}
        #invOverlayRoot .active_item_details .undermark{margin:0;padding:0 0 5px;color:#9c0;font:11px/13px Verdana,Arial,sans-serif;}

        /* === Shop link (fratello di .buffPaginator, nativo: float:right; margin:-18px 32px 0 0) === */
        #invOverlayRoot #activeBuffDetails .shop_link{position:relative;float:right;margin:2px 32px 0 0;color:#fff;font:11px/11px Verdana,Arial,sans-serif;text-decoration:none;white-space:nowrap;z-index:20;}
        #invOverlayRoot #activeBuffDetails .shop_link:hover{text-decoration:underline;}

        /* === Activation button (143×50, native sprite 1:1 OGame) === */
        #invOverlayRoot #activationButton{line-height:1;padding:0;border:0;font-style:inherit;font-family:inherit;z-index:1;background:transparent url(//gf3.geo.gfsrv.net/cdneb/f5f81e8302aaad56c958c033677fb8.png) 0 0 no-repeat;display:table;font-weight:bold;text-decoration:none;text-align:center;float:right;color:#848484;background-position:0 -108px;cursor:default;font-size:12px;height:50px;left:auto;margin:0 auto;position:relative;width:143px;}
        #invOverlayRoot #activationButton.build-it{background-position:0 0;color:#fff;cursor:pointer;}
        #invOverlayRoot #activationButton.build-it_disabled{background-position:0 -108px;color:#848484;cursor:default;}
        #invOverlayRoot #activationButton > span{display:table-cell;vertical-align:middle;width:143px;height:50px;text-align:center;font:700 12px/1 Verdana,Arial,sans-serif;padding:0 6px;}
    </style>
    <div id="invOverlayRoot" aria-hidden="true">
        <div id="invOverlayDetail" style="display:block;">
            <div id="activeBuffDetails">
                <a class="close_details" href="javascript:void(0);" onclick="invOverlayHide();return false;"></a>
                <h2 class="detail_screen_h2">{{ __('t_shop_items.overlay_title') }}</h2>

                <div id="js_activeItemSliderBox" class="active_item_slider">
                    <div class="anythingSlider activeSlider">
                        <div class="anythingWindow">
                            <ul id="js_activeItemSlider" class="anythingBase horizontal" style="width:{{ count($item_catalog['pages']) * 350 }}px;">
                                @foreach ($item_catalog['pages'] as $pageIdx => $tiles)
                                    <li class="slide_{{ $pageIdx }} panel {{ $pageIdx === 0 ? 'activePage' : '' }}" style="width:350px;height:200px;">
                                        @foreach ($tiles as $tile)
                                            <div class="item_img r_{{ $tile['rarity'] }} js_catalogTile"
                                                 data-ref="{{ $tile['ref'] }}"
                                                 data-title="{{ $tile['title'] }}"
                                                 data-effect="{!! e($tile['description_html']) !!}"
                                                 data-duration="{{ $tile['duration_label'] }}"
                                                 data-owned="{{ $tile['owned'] }}"
                                                 data-can-activate="{{ $tile['can_activate'] ? 1 : 0 }}"
                                                 data-price-dm="{{ $tile['price_dm'] ?? 0 }}"
                                                 data-price-label="{{ $tile['price_label'] ?? '' }}"
                                                 style="background-image:url({{ $tile['image_url'] }}),url({{ $tile['image_url'] }});">
                                                <div class="item_img_box">
                                                    <div class="activation {{ $tile['can_activate'] ? 'enabled' : 'disabled' }}"></div>
                                                    <a class="detail_button" href="javascript:void(0);" ref="{{ $tile['ref'] }}"><span class="ecke"><span class="level amount">{{ $tile['owned'] }}</span></span></a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <span class="arrow back"><a href="#"><span>&laquo;</span></a></span>
                        <span class="arrow forward"><a href="#"><span>&raquo;</span></a></span>
                    </div>

                    @if (count($item_catalog['pages']) > 1)
                        <div class="buffPaginator" id="buffPaginator">
                            <button type="button" class="arrowPrev" data-action="prev">&laquo;</button>
                            <div id="buffPages" class="buffPages">
                                <ul id="panelBuff" class="panelBuff">
                                    @foreach ($item_catalog['pages'] as $pageIdx => $_)
                                        <button type="button" data-page="{{ $pageIdx }}" class="{{ $pageIdx === 0 ? 'activePager' : '' }}" style="display:flex;align-items:center;margin:5px;padding:10px;font-size:14px;{{ $pageIdx === 0 ? 'background-color:rgb(70,80,89);color:rgb(255,255,255);' : '' }}">{{ $pageIdx + 1 }}</button>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="arrowNext" data-action="next">&raquo;</button>
                        </div>
                    @endif
                    <a class="shop_link" href="{{ route('shop.index') }}">&raquo; {{ __('t_shop_items.shop_link') }}</a>
                </div>

                <div class="active_item_details border5px">
                    <div id="itemDetailBox" class="item_detail_content">
                        <h2 class="js_itemName"></h2>
                        <p class="js_itemEffect"></p>
                        <p class="blue_txt"><span class="js_itemDurationStatus">{{ __('t_shop_items.label_duration') }}:</span> <span class="js_itemDuration"></span></p>
                        <p class="blue_txt">{{ __('t_shop_items.label_inventory') }}: <span class="js_itemAmount">0</span></p>
                        <p class="undermark js_itemTimeLeftTxt" style="display:none;">{{ __('t_shop_items.label_time_left') }}: <span class="js_itemTimeLeft"></span></p>
                    </div>
                    <a id="activationButton" class="dm buyAndActivate build-it_disabled" href="javascript:void(0);"><span>{{ __('t_shop_items.btn_activate') }}</span></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // === buffBar active boosts countdown ===
        (function () {
            if (window.__buffBarTickerInit) return;
            window.__buffBarTickerInit = true;
            var serverNow = {{ (int) ($server_now_ts ?? 0) }};
            var clientOffset = (Date.now() / 1000) - serverNow;

            function fmt(secs) {
                if (secs <= 0) return '0s';
                var d = Math.floor(secs / 86400), h = Math.floor((secs % 86400) / 3600),
                    m = Math.floor((secs % 3600) / 60), s = secs % 60;
                if (d > 0) return d + 'g ' + h + 'h';
                if (h > 0) return h + 'h ' + m + 'm';
                if (m > 0) return m + 'm ' + s + 's';
                return s + 's';
            }
            function tick() {
                var now = (Date.now() / 1000) - clientOffset;
                var nodes = document.querySelectorAll('#buffBar .active_boost[data-expires-ts]');
                var anyAlive = false;
                nodes.forEach(function (el) {
                    var exp = parseInt(el.getAttribute('data-expires-ts') || '0', 10);
                    var remaining = Math.max(0, Math.floor(exp - now));
                    // Refresh in-tooltip remaining-time spans (only updates if Tipped tooltip is currently open).
                    var spanInTooltip = document.querySelector('.js_boostRemaining[data-expires-ts="' + exp + '"]');
                    if (spanInTooltip) spanInTooltip.textContent = fmt(remaining);
                    if (remaining > 0) anyAlive = true;
                    else el.style.opacity = '0.4';
                });
                if (!anyAlive && nodes.length === 0) {
                    var wrap = document.querySelector('#buffBar .active_items_wrap');
                    if (wrap) wrap.classList.add('hidden');
                }
            }
            tick();
            setInterval(tick, 1000);

            // On hover, force-refresh the remaining-time span inside the freshly-shown tooltip
            document.addEventListener('mouseenter', function (e) {
                var t = e.target;
                if (t && t.classList && t.classList.contains('js_boostTile')) {
                    setTimeout(tick, 50);
                }
            }, true);
        })();

        (function () {
            if (window.__invOverlayInit) return;
            window.__invOverlayInit = true;

            var activateUrl = @json(route('shop.activate'));
            var buyUrl      = @json(route('shop.buy'));
            var activateToken = @json($activateToken);
            var dmBalance     = {{ (int) ($dark_matter ?? 0) }};
            var selectedRef = null;
            var T_BUY_ACTIVATE_FMT = @json(__('t_shop_items.btn_buy_and_activate_price'));
            var T_DM_SHORT         = @json(__('t_shop_items.dm_short'));

            function fmtIntIt(n) {
                n = parseInt(n, 10) || 0;
                return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function root()    { return document.getElementById('invOverlayRoot'); }
            function ensureBodyParent() {
                var r = root();
                if (r && r.parentElement !== document.body) document.body.appendChild(r);
            }
            function positionNearTrigger() {
                var r = root(); if (!r) return;
                var host = document.getElementById('planet');
                var rect = host ? host.getBoundingClientRect() : null;
                if (!rect) {
                    var trigger = document.getElementById('js_openItemSlider');
                    rect = trigger ? trigger.getBoundingClientRect() : {left:100,top:100,width:600,height:300};
                }
                var cardW = 654;
                var scrollX = window.pageXOffset || document.documentElement.scrollLeft || 0;
                var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
                var left = rect.left + scrollX + (rect.width - cardW) / 2;
                var top = rect.top + scrollY - 3;
                r.style.left = left + 'px';
                r.style.top = top + 'px';
            }
            window.invOverlayShow = function () {
                ensureBodyParent();
                var r = root(); if (!r) return;
                r.setAttribute('data-open', '1');
                r.setAttribute('aria-hidden', 'false');
                positionNearTrigger();
                showPage(0);
                var firstTile = r.querySelector('#js_activeItemSlider > li.panel.activePage .js_catalogTile');
                if (firstTile) selectTile(firstTile);
            };
            window.invOverlayHide = function () {
                var r = root(); if (!r) return;
                r.removeAttribute('data-open');
                r.setAttribute('aria-hidden', 'true');
            };

            function showPage(idx) {
                var r = root(); if (!r) return;
                var panels = r.querySelectorAll('#js_activeItemSlider > li.panel');
                panels.forEach(function (li, i) {
                    if (i === idx) { li.classList.add('activePage'); li.style.display = ''; }
                    else { li.classList.remove('activePage'); li.style.display = 'none'; }
                });
                var pagers = r.querySelectorAll('#panelBuff button[data-page]');
                // Rolling window: show only 5 buttons centered on idx (matches OGame native).
                var total = pagers.length;
                var windowSize = 5;
                var start = Math.max(0, idx - Math.floor(windowSize / 2));
                var end = Math.min(total - 1, start + windowSize - 1);
                if (end - start + 1 < windowSize) {
                    start = Math.max(0, end - windowSize + 1);
                }
                pagers.forEach(function (b, i) {
                    var p = parseInt(b.dataset.page, 10);
                    var active = p === idx;
                    var visible = (p >= start && p <= end);
                    b.style.display = visible ? '' : 'none';
                    b.classList.toggle('activePager', active);
                    if (active) {
                        b.style.backgroundColor = 'rgb(70,80,89)';
                        b.style.color = 'rgb(255,255,255)';
                    } else {
                        b.style.backgroundColor = '';
                        b.style.color = '';
                    }
                });
            }

            function activePageIdx() {
                var r = root(); if (!r) return 0;
                var panels = r.querySelectorAll('#js_activeItemSlider > li.panel');
                for (var i = 0; i < panels.length; i++) if (panels[i].classList.contains('activePage')) return i;
                return 0;
            }

            // Map ref → expires_at_ts of active boost (if any). Built from server-side $active_boosts.
            var activeBoostMap = {};
            (function () {
                @foreach ($active_boosts as $boost)
                    @php
                        // Resolve the ShopItem ref (sha1) for this boost so it matches the tile's data-ref
                    @endphp
                @endforeach
                @php $boostMapByRef = []; @endphp
                @foreach ($active_boosts as $boost)
                    @php
                        $shopRef = \OGame\Models\ShopItem::where('name', $boost['label'])->value('ref');
                        if ($shopRef) $boostMapByRef[$shopRef] = $boost['expires_at_ts'];
                    @endphp
                @endforeach
                var data = @json($boostMapByRef);
                Object.assign(activeBoostMap, data);
            })();

            function fmtRemaining(secs) {
                if (secs <= 0) return '0s';
                var d = Math.floor(secs/86400), h = Math.floor((secs%86400)/3600), m = Math.floor((secs%3600)/60), z = secs%60;
                if (d > 0) return d + 'g ' + h + 'h ' + m + 'm ' + z + 's';
                if (h > 0) return h + 'h ' + m + 'm ' + z + 's';
                if (m > 0) return m + 'm ' + z + 's';
                return z + 's';
            }

            function selectTile(tile) {
                var r = root(); if (!r) return;
                r.querySelectorAll('.item_img').forEach(function (t) { t.classList.remove('selected'); });
                tile.classList.add('selected');
                selectedRef = tile.dataset.ref;
                r.querySelector('.js_itemName').textContent = tile.dataset.title || '';
                r.querySelector('.js_itemEffect').innerHTML = tile.dataset.effect || '';
                r.querySelector('.js_itemDuration').textContent = tile.dataset.duration || '';
                r.querySelector('.js_itemAmount').textContent = tile.dataset.owned || '0';

                // If this item has an active boost, show "Durata rimanente" + change button to "Prolunga"
                var expiresTs = activeBoostMap[selectedRef] || 0;
                var timeRow = r.querySelector('.js_itemTimeLeftTxt');
                var timeSpan = r.querySelector('.js_itemTimeLeft');
                var btn = r.querySelector('#activationButton');
                var hasActiveBoost = expiresTs > 0;
                if (hasActiveBoost) {
                    var now = (Date.now() / 1000);
                    var remaining = Math.max(0, Math.floor(expiresTs - now));
                    if (timeRow) timeRow.style.display = '';
                    if (timeSpan) {
                        timeSpan.textContent = fmtRemaining(remaining);
                        timeSpan.dataset.expiresTs = expiresTs;
                    }
                    if (btn) {
                        btn.className = 'dm buyAndActivate build-it';
                        btn.setAttribute('ref', selectedRef);
                        btn.dataset.mode = 'activate';
                        btn.querySelector('span').textContent = @json(__('t_shop_items.btn_extend'));
                        btn.dataset.ref = selectedRef;
                    }
                } else {
                    if (timeRow) timeRow.style.display = 'none';
                    if (btn) {
                        var owned = parseInt(tile.dataset.owned, 10) || 0;
                        var priceDm = parseInt(tile.dataset.priceDm, 10) || 0;
                        if (owned > 0) {
                            // Owned: simple "Attiva", enabled
                            btn.className = 'dm buyAndActivate build-it';
                            btn.dataset.mode = 'activate';
                            btn.querySelector('span').textContent = @json(__('t_shop_items.btn_activate'));
                        } else {
                            // Not owned: "Compra e Attiva per X MO" — enabled iff user can afford
                            var canAfford = dmBalance >= priceDm;
                            btn.className = 'dm buyAndActivate ' + (canAfford ? 'build-it' : 'build-it_disabled');
                            btn.dataset.mode = 'buy_and_activate';
                            var priceTxt = fmtIntIt(priceDm) + ' ' + T_DM_SHORT;
                            // Use innerHTML to support <br> separators (1:1 OGame native).
                            btn.querySelector('span').innerHTML = T_BUY_ACTIVATE_FMT.replace(':price', priceTxt);
                        }
                        btn.setAttribute('ref', selectedRef);
                        btn.dataset.ref = selectedRef;
                    }
                }
            }

            // Live-update the .js_itemTimeLeft span every second when overlay is open
            setInterval(function () {
                var span = document.querySelector('#invOverlayRoot .js_itemTimeLeft[data-expires-ts]');
                if (!span) return;
                var exp = parseInt(span.dataset.expiresTs, 10);
                if (!exp) return;
                var remaining = Math.max(0, Math.floor(exp - Date.now()/1000));
                span.textContent = fmtRemaining(remaining);
            }, 1000);

            function postShop(url) {
                var fd = new FormData();
                fd.append('ref', selectedRef);
                fd.append('ajax', '1');
                fd.append('_token', activateToken);
                fd.append('token', activateToken);
                return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); });
            }

            function doActivate() {
                return postShop(activateUrl).then(function (res) {
                    if (res && res.newToken) activateToken = res.newToken;
                    var msg = (res && res.message && res.message.message) || (res && res.message) || '';
                    if (typeof fadeBox === 'function') fadeBox(msg, !!(res && res.error));
                    if (res && !res.error) setTimeout(function () { location.reload(); }, 800);
                });
            }

            function activate() {
                var r = root(); if (!r || !selectedRef) return;
                var btn = r.querySelector('#activationButton');
                if (!btn || btn.classList.contains('build-it_disabled')) return;
                var mode = btn.dataset.mode || 'activate';
                if (mode === 'buy_and_activate') {
                    // Buy first, then activate the freshly acquired item.
                    postShop(buyUrl).then(function (res) {
                        if (res && res.error) {
                            if (typeof fadeBox === 'function') fadeBox(res.message || 'Errore', true);
                            return;
                        }
                        if (res && res.newToken) activateToken = res.newToken;
                        if (res && typeof res.dark_matter !== 'undefined') {
                            dmBalance = res.dark_matter;
                            var topDm = document.getElementById('resources_darkmatter');
                            if (topDm) topDm.textContent = fmtIntIt(dmBalance);
                        }
                        doActivate();
                    }).catch(function () { if (typeof fadeBox === 'function') fadeBox('Errore di rete', true); });
                } else {
                    doActivate().catch(function () { if (typeof fadeBox === 'function') fadeBox('Errore di rete', true); });
                }
            }

            document.addEventListener('click', function (e) {
                var t = e.target;
                if (!t) return;
                var openTrig = t.closest && t.closest('#js_openItemSlider');
                if (openTrig) { e.preventDefault(); e.stopPropagation(); invOverlayShow(); return; }
                var r = root();
                if (!r || !r.contains(t)) return;
                if (t.closest('.close_details')) { invOverlayHide(); return; }
                var pageBtn = t.closest('#panelBuff button[data-page]');
                if (pageBtn) { showPage(parseInt(pageBtn.dataset.page, 10)); return; }
                var arr = t.closest('.arrow.back, .arrow.forward, .arrowPrev, .arrowNext');
                if (arr) {
                    e.preventDefault(); e.stopPropagation();
                    var total = r.querySelectorAll('#js_activeItemSlider > li.panel').length;
                    var cur = activePageIdx();
                    var isNext = arr.classList.contains('forward') || arr.classList.contains('arrowNext');
                    var next = isNext ? cur + 1 : cur - 1;
                    if (next < 0 || next >= total) return;
                    showPage(next);
                    return;
                }
                var tile = t.closest('.js_catalogTile');
                if (tile) { selectTile(tile); return; }
                if (t.closest('#activationButton')) { activate(); return; }
            }, true);

            document.addEventListener('DOMContentLoaded', ensureBodyParent);
            if (document.readyState !== 'loading') ensureBodyParent();
            window.addEventListener('resize', function () {
                var r = root();
                if (r && r.getAttribute('data-open') === '1') positionNearTrigger();
            });
        })();
    </script>
@endsection
