{{-- Card singolo achievement (replica .achievementOverviewAchievementHolder OGame).
     Atteso: $entry = ['achievement' => Achievement, 'progress' => PlayerAchievementProgress|null, 'tiers' => Collection<AchievementTier>] --}}

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
    $isFullyCompleted = ($completedTier > 0 && $completedTier >= $totalTiers);
    // Tier mostrato di default: il primo non ancora completato (o l'ultimo se tutto completato).
    $visibleTier = ($completedTier < $totalTiers) ? ($completedTier + 1) : $totalTiers;
@endphp

<div class="achievementOverviewAchievementHolder {{ $isFullyCompleted ? 'completed' : '' }}"
     data-achievement-id="{{ $achievement->id }}"
     data-completed-tier="{{ $completedTier }}"
     data-total-tiers="{{ $totalTiers }}">

    <div class="achievementTitleAndTierSelectionContainer">
        <div class="achievementOverviewAchievementTitle">
            <span>#{{ $achievement->display_number }} - {{ __($achievement->name_key) }}</span>
        </div>
        <div class="achievementOverviewTierSelectionContainer">
            @foreach($tiers as $tier)
                <button type="button"
                        class="custom_btn titleTypeSelection achievementTierBtn {{ $tier->tier === $visibleTier ? 'selected' : '' }} {{ $tier->tier <= $completedTier ? 'completed' : '' }}"
                        data-tier="{{ $tier->tier }}"
                        title="{{ __('t_ingame.achievements.tier') }} {{ $tier->tier }}/{{ $totalTiers }}">
                    @for($i = 0; $i < $tier->tier; $i++)
                        <span class="achTierStar"></span>
                    @endfor
                </button>
            @endforeach
        </div>
    </div>

    <div class="achievementOverviewTiersContainer">
        @foreach($tiers as $tier)
            <div class="tier_{{ $tier->tier }} achievementTierContainer {{ $tier->tier === $visibleTier ? 'visible' : '' }}"
                 data-tier="{{ $tier->tier }}">
                <div class="achievementTierContainerData">
                    <div class="achievementTierStatus">
                        <div class="description">{{ $tier->description_text ?: __($achievement->description_key) }}</div>
                        <div>
                            <div class="unlockedOrProgress">
                                <div class="progressParent">
                                    @php
                                        $progressForTier = ($tier->tier <= $completedTier) ? $tier->target : min($currentValue, $tier->target);
                                        $progressPercent = $tier->target > 0 ? min(100, round(($progressForTier / $tier->target) * 100)) : 0;
                                    @endphp
                                    <progress class="achievementProgress" value="{{ $progressForTier }}" max="{{ $tier->target }}"></progress>
                                </div>
                                <div class="achievementProgressLabel">
                                    <span class="progressCurrent">{{ $progressForTier }}</span>
                                    <span> / </span>
                                    <span class="progressTarget">{{ $tier->target }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="achievementReward">
                    <div class="rewardTitle">{{ __('t_ingame.achievements.reward') }}</div>
                    @php $tierUnlocked = $tier->tier <= $completedTier; @endphp
                    @if($tier->reward_type === 'skin')
                        <span class="space-object-skin {{ $tier->reward_machine_name }} {{ $tierUnlocked ? 'unlocked' : 'locked' }}"
                              style="background-image: url('/img/achievements/skins/{{ $tier->reward_machine_name }}.png');"></span>
                        <div class="rewardDescription">{{ __('t_ingame.achievements.reward_skin') }}</div>
                    @elseif($tier->reward_type === 'avatar')
                        @php
                            // Hash deterministico → due colori per avatar diverso (placeholder fino a quando
                            // non scarichiamo gli sprite reali Gameforge per i 60 avatar).
                            $h = crc32($tier->reward_machine_name);
                            $hue1 = $h % 360;
                            $hue2 = ($h >> 8) % 360;
                            $avatarStyle = sprintf(
                                'background: linear-gradient(135deg, hsl(%d,55%%,38%%) 0%%, hsl(%d,55%%,18%%) 100%%);',
                                $hue1, $hue2
                            );
                        @endphp
                        <span class="profile-picture avatar-placeholder {{ $tier->reward_machine_name }} {{ $tierUnlocked ? 'unlocked' : 'locked' }}"
                              style="{{ $avatarStyle }}"
                              data-avatar-id="{{ $tier->reward_machine_name }}">
                            <span class="avatar-initial">A{{ $achievement->display_number }}<br>T{{ $tier->tier }}</span>
                            @unless($tierUnlocked)
                                <span class="avatar-lock-overlay"></span>
                            @endunless
                        </span>
                        <div class="rewardDescription">{{ __('t_ingame.achievements.reward_avatar') }}</div>
                    @else
                        <div class="rewardTypeTitleContainer {{ $tierUnlocked ? 'unlocked' : 'locked' }}">
                            <div class="rewardTypeTitle">{{ $tier->title_text ?: __('t_ingame.achievements.reward_title') }}</div>
                        </div>
                        <div class="rewardDescription">{{ __('t_ingame.achievements.reward_title') }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
