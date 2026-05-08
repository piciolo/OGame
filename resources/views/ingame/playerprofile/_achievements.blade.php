{{-- Tab Trofei: replica fedele di #achievementsoverviewcomponent OGame.
     Atteso: $achievements (Collection di entry), $unlockedAvatars/Skins/Titles (array<string>),
     $rewardCatalog (array{avatar:[],skin:[],title:[]}), $isOwner (bool). --}}

<div id="achievementsoverviewcomponent">
    <h3>{{ __('t_ingame.profile.tab_achievements') }}</h3>

    <div id="achievementsOverviewCategories">
        <div class="achievementCategory active" data-category="unlocks">
            <span>{{ __('t_ingame.achievements.cat_summary') }}</span>
        </div>
        <div class="achievementCategory" data-category="avatars">
            <span>{{ __('t_ingame.achievements.cat_avatars') }}</span>
        </div>
        <div class="achievementCategory" data-category="spaceObjectSkins">
            <span>{{ __('t_ingame.achievements.cat_skins') }}</span>
        </div>
        <div class="achievementCategory" data-category="titles">
            <span>{{ __('t_ingame.achievements.cat_titles') }}</span>
        </div>
    </div>

    <div id="achievementsOverviewList">

        {{-- ── Riepilogo (achievement) ─────────────────────────────────── --}}
        <div id="achievementContentList_unlocks" class="achievementContentList unlocks">
            <div id="achievementOverviewAchievementFilters" class="achievementFilter">
                <button type="button" class="custom_btn achievementFilterIcon expandAll" id="achievementOverviewExpandAllBtn" title="{{ __('t_ingame.achievements.expand_all') }}">«</button>
                <button type="button" class="custom_btn achievementFilterIcon collapseAll" id="achievementOverviewCollapseAllBtn" title="{{ __('t_ingame.achievements.collapse_all') }}">»</button>
                <div class="horizontalSplit"></div>
                <span class="achievementFilterLabel">{{ __('t_ingame.achievements.show_all') }}</span>
                <div class="achievementFilteringOptions">
                    <button type="button" class="custom_btn green selected" id="achievementOverviewShowAllBtn" data-filter="all">{{ __('t_ingame.achievements.filter_all') }}</button>
                    <button type="button" class="custom_btn" id="achievementOverviewShowUnfinishedBtn" data-filter="unfinished">{{ __('t_ingame.achievements.filter_unfinished') }}</button>
                    <button type="button" class="custom_btn" id="achievementOverviewShowFinishedBtn" data-filter="finished">{{ __('t_ingame.achievements.filter_finished') }}</button>
                </div>
            </div>

            <div class="achievementCardsScroll">
                @forelse($achievements as $entry)
                    @include('ingame.playerprofile._achievement_card', ['entry' => $entry])
                @empty
                    <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
                @endforelse
            </div>
        </div>

        {{-- ── Avatar collezionabili ───────────────────────────────────── --}}
        <div id="achievementContentList_avatars" class="achievementContentList avatars hidden">
            @foreach($rewardCatalog['avatar'] as $machineName)
                @php
                    $unlocked = in_array($machineName, $unlockedAvatars, true);
                    $isCurrent = ($targetPlayer->getUser()->profile_avatar ?? '') === $machineName;
                    // Issue #1: placeholder distinto per machine_name (gradient deterministico)
                    // finché non scarichiamo gli sprite reali Gameforge per i 60 avatar.
                    $h = crc32($machineName);
                    $hue1 = $h % 360;
                    $hue2 = ($h >> 8) % 360;
                    $avatarStyle = sprintf(
                        'background: linear-gradient(135deg, hsl(%d,55%%,38%%) 0%%, hsl(%d,55%%,18%%) 100%%);',
                        $hue1, $hue2
                    );
                @endphp
                <div class="achievementOverviewProfilePictureHolder {{ $unlocked ? 'unlocked' : '' }} {{ $isCurrent ? 'currentSelection' : '' }}"
                     data-machine-name="{{ $machineName }}"
                     data-reward-type="avatar"
                     title="{{ $machineName }}">
                    <span class="profile-picture avatar-placeholder {{ $machineName }} {{ $unlocked ? 'unlocked' : 'locked' }}"
                          style="{{ $avatarStyle }}">
                        <span class="avatar-initial">{{ $machineName }}</span>
                        @unless($unlocked)
                            <span class="avatar-lock-overlay"></span>
                        @endunless
                    </span>
                </div>
            @endforeach
            @if(count($rewardCatalog['avatar']) === 0)
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endif
        </div>

        {{-- ── Skin pianeta ─────────────────────────────────────────────── --}}
        <div id="achievementContentList_spaceObjectSkins" class="achievementContentList spaceObjectSkins hidden">
            @foreach($rewardCatalog['skin'] as $machineName)
                @php
                    $unlocked = in_array($machineName, $unlockedSkins, true);
                    $isCurrent = ($targetPlayer->getUser()->profile_planet_skin ?? '') === $machineName;
                    // Skin reale sempre disponibile (sono renderizzati indipendentemente dallo stato).
                    $bgUrl = '/img/achievements/skins/'.$machineName.'.png';
                @endphp
                <div class="achievementOverviewSpaceObjectSkinHolder {{ $unlocked ? 'unlocked' : '' }} {{ $isCurrent ? 'currentSelection' : '' }}"
                     data-machine-name="{{ $machineName }}"
                     data-reward-type="skin"
                     title="{{ $machineName }}">
                    <span class="space-object-skin {{ $machineName }} {{ $unlocked ? 'unlocked' : 'locked' }}"
                          style="background-image: url('{{ $bgUrl }}');"></span>
                </div>
            @endforeach
            @if(count($rewardCatalog['skin']) === 0)
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endif
        </div>

        {{-- ── Titoli ──────────────────────────────────────────────────── --}}
        <div id="achievementContentList_titles" class="achievementContentList titles hidden">
            @foreach($rewardCatalog['title'] as $machineName)
                @php
                    $unlocked = in_array($machineName, $unlockedTitles, true);
                    $isCurrent = ($targetPlayer->getUser()->profile_title ?? '') === $machineName;
                @endphp
                <div class="achievementOverviewTitleHolder {{ $unlocked ? 'unlocked' : 'locked' }} {{ $isCurrent ? 'currentSelection' : '' }}"
                     data-machine-name="{{ $machineName }}"
                     data-reward-type="title">
                    {{-- Issue #3: testo titolo reale dal DB (achievement_tiers.title_text) --}}
                    <span>{{ $titleTextLookup[$machineName] ?? $machineName }}</span>
                </div>
            @endforeach
            @if(count($rewardCatalog['title']) === 0)
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endif
        </div>

    </div>
</div>
