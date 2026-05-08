{{-- Card achievement — DOM 1:1 OGame (estratto da ogame_trofei_schemas.md).
     Atteso: $entry = ['achievement' => Achievement, 'progress' => …|null, 'tiers' => Collection<AchievementTier>] --}}

@php
    /** @var \OGame\Models\Achievement $achievement */
    $achievement = $entry['achievement'];
    /** @var \OGame\Models\PlayerAchievementProgress|null $progress */
    $progress = $entry['progress'] ?? null;
    $completedTier = $progress->completed_tier ?? 0;
    $currentValue = $progress->current_value ?? 0;
    /** @var \Illuminate\Support\Collection<int, \OGame\Models\AchievementTier> $tiers */
    $tiers = $entry['tiers'];
    $totalTiers = $tiers->count();
    // achievement_id "esterno" usato da OGame: progressivo display_number * 100 + 100, e.g. 1000100, 1000200…
    // Lo replichiamo per allineare il markup (anche se il routing usa $achievement->id)
    $extId = $achievement->display_number * 1000 + 100;
    $visibleTier = ($completedTier < $totalTiers) ? ($completedTier + 1) : $totalTiers;
@endphp

<div id="achievementOverviewAchievementHolder_{{ $extId }}"
     class="achievementOverviewAchievementHolder">
    <div class="achievementTitleAndTierSelectionContainer">
        <div class="achievementOverviewAchievementTitle">
            <span>#{{ $achievement->display_number }} - {{ __($achievement->name_key) }}</span>
        </div>

        <div class="achievementOverviewTierSelectionContainer">
            @foreach($tiers as $tier)
                <gradient-button h16 w50>
                    <button class="custom_btn {{ $tier->tier === $visibleTier ? 'selected' : '' }}"
                            onclick="showAchievementTier('{{ $extId }}', {{ $tier->tier }})">
                        @for($i = 0; $i < $tier->tier; $i++)<img src="/cdn/img/avatars/tierstar.png" style="width: 8px; height: 8px;">@endfor
                    </button>
                </gradient-button>
            @endforeach
        </div>
    </div>

    <div class="achievementOverviewTiersContainer">
        @foreach($tiers as $tier)
            @php
                $tierUnlocked = $tier->tier <= $completedTier;
                $progressForTier = $tierUnlocked ? $tier->target : min($currentValue, $tier->target);
            @endphp
            <div id="achievementTier_{{ $extId }}_{{ $tier->tier }}"
                 class="tier_{{ $tier->tier }} achievementTierContainer {{ $tier->tier === $visibleTier ? 'visible' : '' }} {{ $tierUnlocked ? 'unlocked' : '' }}">
                <div style="display: flex; width: calc(75% - 10px); position: relative;">
                    <div class="achievementTierContainerTitle">
                        @for($i = 0; $i < $tier->tier; $i++)<img src="/cdn/img/avatars/tierstar.png" style="width: 11px; height: 11px;">@endfor
                    </div>
                    <div class="achievementTierContainerData">
                        <div class="achievementTierStatus">
                            <div class="description">{{ $tier->description_text ?: __($achievement->description_key) }}</div>
                            <div style="display: flex; justify-content: space-between;">
                                <div class="unlockedOrProgress">
                                    <div class="progressParent" style="margin-bottom: 5px;">
                                        <progress id="achievementProgress_{{ $extId }}_{{ $tier->tier }}"
                                                  class="achievementProgress progress_{{ $tierUnlocked ? '100' : '0' }}"
                                                  max="{{ $tier->target }}"
                                                  value="{{ $progressForTier }}"></progress>
                                    </div>
                                    <div class="achievementProgressLabel" style="position: relative">
                                        {{ $progressForTier }}
                                        / <span class="progressTarget"> {{ $tier->target }}</span>
                                    </div>
                                </div>
                                <div style="position: absolute; right: 10px; bottom: 10px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="achievementReward">
                    <div class="rewardTitle">{{ __('t_ingame.achievements.reward') }}</div>
                    @if($tier->reward_type === 'skin')
                        @php
                            $skinBase = 'img/achievements/skins/'.$tier->reward_machine_name;
                            $skinUrl = null;
                            foreach (['.png', '.jpg'] as $ext) {
                                if (file_exists(public_path($skinBase.$ext))) { $skinUrl = '/'.$skinBase.$ext; break; }
                            }
                        @endphp
                        <space-object-skin sq100 class="{{ $tier->reward_machine_name }} {{ $tierUnlocked ? '' : 'locked' }}">
                            @if($skinUrl)<img src="{{ $skinUrl }}" alt="">@endif
                        </space-object-skin>
                        <div class="rewardDescription">{{ __('t_ingame.achievements.reward_skin') }}</div>
                    @elseif($tier->reward_type === 'avatar')
                        @php
                            $avBase = 'img/achievements/avatars/'.$tier->reward_machine_name;
                            $avUrl = null;
                            foreach (['.jpg', '.png'] as $ext) {
                                if (file_exists(public_path($avBase.$ext))) { $avUrl = '/'.$avBase.$ext; break; }
                            }
                        @endphp
                        <profile-picture sq100 class="{{ $tier->reward_machine_name }} {{ $tierUnlocked ? '' : 'locked' }}">
                            @if($avUrl)<picture><img src="{{ $avUrl }}" alt=""></picture>@endif
                        </profile-picture>
                        <div class="rewardDescription">{{ __('t_ingame.achievements.reward_avatar') }}</div>
                    @else
                        <div sq100 class="rewardTypeTitleContainer {{ $tierUnlocked ? '' : 'locked' }}">
                            <div class="rewardTypeTitle" lang="it">{{ $tier->title_text ?: '' }}</div>
                        </div>
                        <div class="rewardDescription">{{ __('t_ingame.achievements.reward_title') }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
