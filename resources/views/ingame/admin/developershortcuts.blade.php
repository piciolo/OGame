@extends('ingame.layouts.main')

@section('content')

    @php /** @var \OGame\Services\PlanetService $currentPlanet */ @endphp

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="resourcesettingscomponent" class="maincontent">
        <div id="planet" class="shortHeader">
            <h2>{{ __('t_ingame.admin.dev_title') }}</h2>
        </div>

        <div id="buttonz">
            <div class="header">
                <h2>{{ __('t_ingame.admin.dev_title') }}</h2>
            </div>
            <div class="content">
                <div class="buddylistContent" style="margin-bottom: 60px;">

                    {{-- Quick links to other admin tools --}}
                    <div class="group bborder" style="display:flex;gap:10px;align-items:center;padding:8px 0;margin-bottom:10px;">
                        <span class="textBeefy" style="color:#8ec8f0;">Admin tools:</span>
                        <a href="{{ route('admin.fleettiming.index') }}" class="btn_blue" style="padding:4px 12px;font-size:12px;">Fleet Timing Control</a>
                        <a href="{{ route('admin.serversettings.index') }}" class="btn_blue" style="padding:4px 12px;font-size:12px;">Server Settings</a>
                    </div>

                    {{-- Masquerade as user (admin-only helper) --}}
                    <form action="{{ route('admin.developershortcuts.impersonate') }}" method="post" style="margin-bottom: 20px;">
                        {{ csrf_field() }}
                        <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_masquerade') }}</p>
                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy" for="masquerade_username">{{ __('t_ingame.admin.dev_username') }}</label>
                                <div class="thefield">
                                    <input type="text"
                                           id="masquerade_username"
                                           name="username"
                                           class="textInput w150 textCenter textBeefy"
                                           placeholder="{{ __('t_ingame.admin.dev_username_placeholder') }}">
                                </div>
                            </div>
                            <div class="fieldwrapper" style="text-align: center; margin-top: 10px;">
                                <input type="submit" class="btn_blue" value="{{ __('t_ingame.admin.dev_masquerade_btn') }}">
                            </div>
                        </div>
                    </form>

                    <form action="{{ route('admin.developershortcuts.update') }}" name="form" method="post">
                        {{ csrf_field() }}
                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_update_planet') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <input type="submit" class="btn_blue" name="set_mines" value="{{ __('t_ingame.admin.dev_set_mines') }}">
                                        <input type="submit" class="btn_blue" name="set_storages" value="{{ __('t_ingame.admin.dev_set_storages') }}">
                                        <input type="submit" class="btn_blue" name="set_shipyard" value="{{ __('t_ingame.admin.dev_set_shipyard') }}">
                                        <input type="submit" class="btn_blue" name="set_research" value="{{ __('t_ingame.admin.dev_set_research') }}">
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_add_units') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_units_amount') }}</label>
                                        <div class="thefield">
                                            <input type="text" pattern="^[0-9,.kmb]+$" class="textInput w50 textCenter textBeefy" placeholder="1" size="2" name="amount_of_units">
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        @php /** @var OGame\GameObjects\Models\UnitObject $unit */ @endphp
                                        @foreach ($units as $unit)
                                            <input type="submit" name="unit_{{ $unit->id }}" class="btn_blue" value="{{ $unit->title }}">
                                        @endforeach
                                        <input type="submit" class="btn_blue" value="{{ __('t_ingame.admin.dev_light_fighter') }}">
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_set_building') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_level_to_set') }}</label>
                                        <div class="thefield">
                                            <input type="text" pattern="^[0-9]+$" placeholder="0" class="textInput w50 textCenter textBeefy" size="2" name="building_level">
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        @foreach ($buildings as $building)
                                            <input type="submit" name="building_{{ $building->id }}" class="btn_blue" value="{{ $building->title }}">
                                        @endforeach
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_set_research_level') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_level_to_set') }}</label>
                                        <div class="thefield">
                                            <input type="text" pattern="^[0-9]+$" placeholder="0" class="textInput w50 textCenter textBeefy" size="2" name="research_level">
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        @foreach ($research as $tech)
                                            <input type="submit" name="research_{{ $tech->id }}" class="btn_blue" value="{{ $tech->title }}">
                                        @endforeach
                                    </div>
                                </div>
                                <!-- TODO: refactor this to add/substract DM to any player instead of free changes, this removes unecessary/complex free change logic -->
                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_class_settings') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        @php
                                            $freeClassChanges = app(\OGame\Services\SettingsService::class)->get('dev_free_class_changes', false);
                                        @endphp
                                        @if($freeClassChanges)
                                            <input type="submit" class="btn_blue" name="disable_free_class_changes" value="{{ __('t_ingame.admin.dev_disable_free_class') }}">
                                        @else
                                            <input type="submit" class="btn_blue" name="enable_free_class_changes" value="{{ __('t_ingame.admin.dev_enable_free_class') }}">
                                        @endif
                                        <input type="submit" class="btn_blue" name="reset_character_class" value="{{ __('t_ingame.admin.dev_reset_class') }}">
                                        <a href="{{ route('characterclass.index') }}" class="btn_blue" style="display: inline-block; padding: 5px 10px; text-decoration: none;">{{ __('t_ingame.admin.dev_goto_class') }}</a>
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_reset_planet') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <input type="submit" class="btn_blue" name="reset_buildings" value="{{ __('t_ingame.admin.dev_reset_buildings') }}">
                                        <input type="submit" class="btn_blue" name="reset_research" value="{{ __('t_ingame.admin.dev_reset_research') }}">
                                        <input type="submit" class="btn_blue" name="reset_units" value="{{ __('t_ingame.admin.dev_reset_units') }}">
                                        <input type="submit" class="btn_blue" name="reset_resources" value="{{ __('t_ingame.admin.dev_reset_resources') }}">
                                    </div>
                                </div>
                            </form>

                            <form action="{{ route('admin.developershortcuts.update-resources') }}" name="form" method="post">
                                {{ csrf_field() }}
                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_add_resources') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <div class="smallFont">{{ __('t_ingame.admin.dev_resources_desc') }}</div>
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_coordinates') }}</label>
                                        <div class="thefield" style="display: flex; gap: 10px;">
                                            <div>
                                                <label for="galaxy">{{ __('t_ingame.admin.dev_galaxy') }}</label>
                                                <input type="text" id="galaxy" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->galaxy }}" min="1" max="{{ $settings->numberOfGalaxies() }}" name="galaxy">
                                            </div>
                                            <div>
                                                <label for="system">{{ __('t_ingame.admin.dev_system') }}</label>
                                                <input type="text" id="system" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->system }}" min="1" max="499" name="system">
                                            </div>
                                            <div>
                                                <label for="position">{{ __('t_ingame.admin.dev_position') }}</label>
                                                <input type="text" id="position" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->position }}" min="1" max="15" name="position">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper"><label class="styled textBeefy">{{ __('t_ingame.admin.dev_resources_label') }}</label>
                                        <div class="thefield" style="display: flex; flex-direction: column; gap: 10px;">
                                            @foreach (\OGame\Models\Enums\ResourceType::cases() as $resource)
                                                <div style="display: flex; gap: 10px;">
                                                    <label for="{{ $resource->value }}" style="min-width: 80px;">{{ $resource->name }}:</label>
                                                    <input type="text" id="{{ $resource->value }}" pattern="^[-+0-9,.kmb]+$"
                                                           class="textInput w100 textCenter textBeefy"
                                                           placeholder="0" name="{{ $resource->value }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center;">
                                        <input type="submit" class="btn_blue" name="update_resources_planet" value="{{ __('t_ingame.admin.dev_update_resources_planet') }}">
                                        <input type="submit" class="btn_blue" name="update_resources_moon" value="{{ __('t_ingame.admin.dev_update_resources_moon') }}">
                                    </div>
                                </div>
                            </form>

                            <form action="{{ route('admin.developershortcuts.create-at-coords') }}" name="form" method="post">
                                {{ csrf_field() }}
                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_create_planet_moon') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_coordinates') }}</label>
                                        <div class="thefield" style="display: flex; gap: 10px;">
                                            <div>
                                                <label for="galaxy">{{ __('t_ingame.admin.dev_galaxy') }}</label>
                                                <input type="text" id="galaxy" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->galaxy }}" min="1" max="{{ $settings->numberOfGalaxies() }}" name="galaxy">
                                            </div>
                                            <div>
                                                <label for="system">{{ __('t_ingame.admin.dev_system') }}</label>
                                                <input type="text" id="system" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->system }}" min="1" max="499" name="system">
                                            </div>
                                            <div>
                                                <label for="position">{{ __('t_ingame.admin.dev_position') }}</label>
                                                <input type="text" id="position" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->position }}" min="1" max="15" name="position">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_moon_size') }}</label>
                                        <div class="thefield" style="display: flex; gap: 15px; align-items: flex-start;">
                                            <div style="flex: 1;">
                                                <label for="moon_debris" style="display: block; margin-bottom: 5px;">{{ __('t_ingame.admin.dev_debris_amount') }}</label>
                                                <input type="text" id="moon_debris" pattern="^[-+0-9,.kmb]+$" class="textInput textCenter textBeefy"
                                                       style="width: 100%;" value="2000000" placeholder="2000000" name="moon_debris" title="Total debris (metal+crystal+deuterium) that determines moon size">
                                                <span style="display: block; font-size: 0.9em; color: #666; margin-top: 5px;">Examples: 100k, 500k, 1M, 2M</span>
                                            </div>
                                            <div style="flex: 1;">
                                                <label for="moon_factor" style="display: block; margin-bottom: 5px;">{{ __('t_ingame.admin.dev_x_factor') }}</label>
                                                <input type="text" id="moon_factor" pattern="^[0-9]+$" class="textInput textCenter textBeefy"
                                                       style="width: 100%;" value="" placeholder="Random" name="moon_factor" min="10" max="20" title="X factor in formula (10-20). Leave blank for random.">
                                                <span style="display: block; font-size: 0.9em; color: #666; margin-top: 5px;">Leave blank = random</span>
                                            </div>
                                        </div>
                                        <span style="display: block; font-size: 0.9em; color: #999; margin-top: 8px; font-style: italic;">Formula: diameter = floor((x + 3*debris/100000)^0.5 * 1000)</span>
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center; margin-bottom: 20px;">
                                        <input type="submit" class="btn_blue" name="create_planet" value="{{ __('t_ingame.admin.dev_create_planet') }}">
                                        <input type="submit" class="btn_blue" name="create_moon" value="{{ __('t_ingame.admin.dev_create_moon') }}">
                                        <input type="submit" class="btn_blue" name="delete_planet" value="{{ __('t_ingame.admin.dev_delete_planet') }}">
                                        <input type="submit" class="btn_blue" name="delete_moon" value="{{ __('t_ingame.admin.dev_delete_moon') }}">
                                    </div>
                                </div>
                            </form>

                            <form action="{{ route('admin.developershortcuts.create-debris') }}" name="form" method="post">
                                {{ csrf_field() }}
                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_create_debris') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_coordinates') }}</label>
                                        <div class="thefield" style="display: flex; gap: 10px;">
                                            <div>
                                                <label for="galaxy">{{ __('t_ingame.admin.dev_galaxy') }}</label>
                                                <input type="text" id="galaxy" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->galaxy }}" min="1" max="{{ $settings->numberOfGalaxies() }}" name="galaxy">
                                            </div>
                                            <div>
                                                <label for="system">{{ __('t_ingame.admin.dev_system') }}</label>
                                                <input type="text" id="system" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->system }}" min="1" max="499" name="system">
                                            </div>
                                            <div>
                                                <label for="position">{{ __('t_ingame.admin.dev_position') }} (1-16)</label>
                                                <input type="text" id="position" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->position }}" min="1" max="16" name="position">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_debris_resources_label') }}</label>
                                        <div class="thefield" style="display: flex; flex-direction: column; gap: 10px;">
                                            @foreach (\OGame\Models\Enums\ResourceType::cases() as $resource)
                                                <div style="display: flex; gap: 10px;">
                                                    <label for="{{ $resource->value }}" style="min-width: 80px;">{{ $resource->name }}:</label>
                                                    <input type="text" id="{{ $resource->value }}" pattern="^[-+0-9,.kmb]+$"
                                                           class="textInput w100 textCenter textBeefy"
                                                           placeholder="0" name="{{ $resource->value }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center; margin-bottom: 20px;">
                                        <input type="submit" class="btn_blue" name="create_debris" value="{{ __('t_ingame.admin.dev_create_debris_btn') }}">
                                        <input type="submit" class="btn_blue" name="delete_debris" value="{{ __('t_ingame.admin.dev_delete_debris_btn') }}">
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center; margin-bottom: 50px; padding-top: 10px; border-top: 1px solid #444;">
                                        <p style="margin-bottom: 10px; color: #999; font-size: 0.9em;">{{ __('t_ingame.admin.dev_quick_shortcut_desc') }}</p>
                                        <button type="submit" class="btn_blue" onclick="document.getElementById('position').value='16'; document.getElementById('metal').value='100000'; document.getElementById('crystal').value='50000'; document.getElementById('deuterium').value='25000'; return true;">
                                            {{ __('t_ingame.admin.dev_create_expedition_debris') }}
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form action="{{ route('admin.developershortcuts.update-dark-matter') }}" name="form" method="post">
                                {{ csrf_field() }}
                                <p class="box_highlight textCenter no_buddies">{{ __('t_ingame.admin.dev_add_dm') }}</p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <div class="smallFont">{{ __('t_ingame.admin.dev_dm_desc') }}</div>
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_coordinates') }}</label>
                                        <div class="thefield" style="display: flex; gap: 10px;">
                                            <div>
                                                <label for="dm_galaxy">{{ __('t_ingame.admin.dev_galaxy') }}</label>
                                                <input type="text" id="dm_galaxy" pattern="^[0-9]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->galaxy }}" min="1" max="{{ $settings->numberOfGalaxies() }}" name="galaxy">
                                            </div>
                                            <div>
                                                <label for="dm_system">{{ __('t_ingame.admin.dev_system') }}</label>
                                                <input type="text" id="dm_system" pattern="^[0-9]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->system }}" min="1" max="499" name="system">
                                            </div>
                                            <div>
                                                <label for="dm_position">{{ __('t_ingame.admin.dev_position') }}</label>
                                                <input type="text" id="dm_position" pattern="^[0-9]+$" class="textInput w50 textCenter textBeefy"
                                                       value="{{ $currentPlanet->getPlanetCoordinates()->position }}" min="1" max="15" name="position">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy">{{ __('t_ingame.admin.dev_dm_amount') }}</label>
                                        <div class="thefield">
                                            <input type="text" id="dark_matter" pattern="^[-+0-9,.kmb]+$"
                                                   class="textInput w100 textCenter textBeefy"
                                                   placeholder="0" name="dark_matter">
                                        </div>
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center;">
                                        <input type="submit" class="btn_blue" name="update_dark_matter" value="{{ __('t_ingame.admin.dev_update_dm') }}">
                                    </div>
                                </div>
                            </form>

                            {{-- ===== AUCTIONEER TEST SHORTCUTS ===== --}}
                            <p class="box_highlight textCenter no_buddies">@lang('Auctioneer test shortcuts')</p>
                            <div class="group bborder" style="display: block;">
                                <div class="fieldwrapper">
                                    <div class="smallFont">@lang('Skip the natural Waiting/Running countdown to test a full auction cycle in seconds. Resources spent on bids are never refunded (matches OGame rules).')</div>
                                </div>
                                <div class="fieldwrapper" style="text-align: center;">
                                    <form action="{{ route('admin.developershortcuts.auctioneer.spawn') }}" method="post" style="display: inline-block; margin: 2px;">
                                        {{ csrf_field() }}
                                        <input type="submit" class="btn_blue" value="@lang('Spawn new auction (Waiting)')">
                                    </form>
                                    <form action="{{ route('admin.developershortcuts.auctioneer.force-start') }}" method="post" style="display: inline-block; margin: 2px;">
                                        {{ csrf_field() }}
                                        <input type="submit" class="btn_blue" value="@lang('Force start (Waiting → Running)')">
                                    </form>
                                    <form action="{{ route('admin.developershortcuts.auctioneer.force-end') }}" method="post" style="display: inline-block; margin: 2px;"
                                          onsubmit="return confirm('Force-end the running auction now? Prize will be assigned to the current highest bidder.');">
                                        {{ csrf_field() }}
                                        <input type="submit" class="btn_blue" value="@lang('Force end (assign prize now)')">
                                    </form>
                                    <form action="{{ route('admin.developershortcuts.auctioneer.tick') }}" method="post" style="display: inline-block; margin: 2px;">
                                        {{ csrf_field() }}
                                        <input type="submit" class="btn_blue" value="@lang('Run tick (state machine)')">
                                    </form>
                                    <form action="{{ route('admin.developershortcuts.auctioneer.cancel') }}" method="post" style="display: inline-block; margin: 2px;"
                                          onsubmit="return confirm('Cancel the current auction without delivering any prize? This is for cleanup only.');">
                                        {{ csrf_field() }}
                                        <input type="submit" class="btn_blue" value="@lang('Cancel current auction')">
                                    </form>
                                </div>

                                {{-- Spawn specific lot template --}}
                                @php
                                $familyLabels = [
                                    'resource_boost_metal'     => 'Amplificatore di metallo',
                                    'resource_boost_crystal'   => 'Amplificatore di cristallo',
                                    'resource_boost_deuterium' => 'Amplificatore di deuterio',
                                    'resource_boost_energy'    => 'Amplificatore di energia',
                                    'booster_kraken'           => 'KRAKEN',
                                    'booster_newtron'          => 'NEWTRON',
                                    'booster_detroid'          => 'DETROID',
                                ];
                                $tierLabel = ['bronze'=>'Bronzo','silver'=>'Argento','gold'=>'Oro','platinum'=>'Platino'];
                                @endphp
                                <div class="fieldwrapper" style="margin-top: 10px;">
                                    <form action="{{ route('admin.developershortcuts.auctioneer.spawn-specific') }}" method="post" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        {{ csrf_field() }}
                                        <label style="white-space: nowrap;">@lang('Spawn specific lot:')</label>
                                        <select name="template_id" style="flex: 1; min-width: 320px; max-width: 520px;">
                                            @foreach($lotTemplates as $familyKey => $group)
                                                <optgroup label="── {{ $familyLabels[$familyKey] ?? $familyKey }} ──">
                                                    @foreach($group as $tpl)
                                                        @php
                                                            $p = $tpl->lot_payload ?? [];
                                                            if ($tpl->lot_type->value === 'resource_boost') {
                                                                $detail = '+' . ($p['percent'] ?? '?') . '%';
                                                            } else {
                                                                $secs = $p['duration_seconds'] ?? 0;
                                                                $detail = $secs >= 86400
                                                                    ? '-' . round($secs/86400) . ' giorno'
                                                                    : ($secs >= 3600 ? '-' . round($secs/3600) . 'h' : '-' . round($secs/60) . 'm');
                                                            }
                                                        @endphp
                                                        <option value="{{ $tpl->id }}">{{ $tpl->lot_title }} {{ $detail }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        <input type="submit" class="btn_blue" value="@lang('Spawn')">
                                    </form>
                                </div>
                            </div>
                </div>
            </div>
            </div>

        <script language="javascript">
            initBBCodes();
            initOverlays();
        </script>
    </div>

@endsection
