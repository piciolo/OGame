{{-- Tab Trofei — DOM 1:1 OGame (estratto da ogame_trofei_schemas.md). --}}
{{-- Atteso: $achievements (Collection), $unlockedAvatars/Skins/Titles, $rewardCatalog,
            $titleTextLookup, $isOwner, $targetPlayer. --}}

<div id="achievementsoverviewcomponent">
    <h3>{{ __('t_ingame.profile.tab_achievements') }}</h3>

    <div id="achievementsOverviewCategories">
        <div class="achievementCategory active" data-achievement-category-id="unlocks" onclick="changeAchievementCategory(this)">
            <span>{{ __('t_ingame.achievements.cat_summary') }}</span>
        </div>
        <div class="achievementCategory" data-achievement-category-id="avatars" onclick="changeAchievementCategory(this)">
            <span>{{ __('t_ingame.achievements.cat_avatars') }}</span>
        </div>
        <div class="achievementCategory" data-achievement-category-id="spaceObjectSkins" onclick="changeAchievementCategory(this)">
            <span>{{ __('t_ingame.achievements.cat_skins') }}</span>
        </div>
        <div class="achievementCategory" data-achievement-category-id="titles" onclick="changeAchievementCategory(this)">
            <span>{{ __('t_ingame.achievements.cat_titles') }}</span>
        </div>
    </div>

    <div id="achievementsOverviewList" onscroll="if (window.tippy) tippy.hideAll()">

        {{-- ── Riepilogo (achievement) ─────────────────────────────────── --}}
        <div id="achievementContentList_unlocks" class="achievementContentList unlocks">
            <div id="achievementOverviewAchievementFilters" class="achievementFilter">
                <gradient-button h20 id="achievementOverviewExpandAllBtn">
                    <button class="custom_btn" title="{{ __('t_ingame.achievements.expand_all') }}" onclick="if (window.expandAllAchievementTiers) expandAllAchievementTiers()">
                        <span>{{ __('t_ingame.achievements.expand_all') }}</span>
                    </button>
                </gradient-button>
                <gradient-button h20 id="achievementOverviewCollapseAllBtn" style="display:none;">
                    <button class="custom_btn" title="{{ __('t_ingame.achievements.collapse_all') }}" onclick="if (window.collapseAllAchievementTiers) collapseAllAchievementTiers()">
                        <span>{{ __('t_ingame.achievements.collapse_all') }}</span>
                    </button>
                </gradient-button>
                <div class="horizontalSplit"></div>
                <div class="achievementFilteringOptions">
                    <gradient-button h20 id="achievementOverviewShowAllBtn">
                        <button class="custom_btn active" title="{{ __('t_ingame.achievements.filter_all') }}" onclick="if (window.filterAchievements) filterAchievements(this, 'all')">
                            <span>{{ __('t_ingame.achievements.filter_all') }}</span>
                        </button>
                    </gradient-button>
                    <gradient-button h20 id="achievementOverviewShowUnfinishedBtn">
                        <button class="custom_btn" title="{{ __('t_ingame.achievements.filter_unfinished') }}" onclick="if (window.filterAchievements) filterAchievements(this, 'unfinished')">
                            <span>{{ __('t_ingame.achievements.filter_unfinished') }}</span>
                        </button>
                    </gradient-button>
                    <gradient-button h20 id="achievementOverviewShowFinishedBtn">
                        <button class="custom_btn" title="{{ __('t_ingame.achievements.filter_finished') }}" onclick="if (window.filterAchievements) filterAchievements(this, 'finished')">
                            <span>{{ __('t_ingame.achievements.filter_finished') }}</span>
                        </button>
                    </gradient-button>
                </div>
            </div>

            @forelse($achievements as $entry)
                @include('ingame.playerprofile._achievement_card', ['entry' => $entry])
            @empty
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endforelse
        </div>

        {{-- ── Avatar collezionabili ───────────────────────────────────── --}}
        <div id="achievementContentList_avatars" class="achievementContentList avatars hidden">
            @foreach($rewardCatalog['avatar'] as $machineName)
                @php
                    $unlocked = in_array($machineName, $unlockedAvatars, true);
                    $isCurrent = ($targetPlayer->getUser()->profile_avatar ?? '') === $machineName;
                @endphp
                @php
                    $avatarBase = 'img/achievements/avatars/'.$machineName;
                    $avatarUrl = null;
                    foreach (['.jpg', '.png'] as $ext) {
                        if (file_exists(public_path($avatarBase.$ext))) { $avatarUrl = '/'.$avatarBase.$ext; break; }
                    }
                @endphp
                <div class="achievementOverviewProfilePictureHolder {{ $isCurrent ? 'selected' : '' }}"
                     data-avatar-id="{{ $machineName }}">
                    <profile-picture class="{{ $machineName }} {{ $unlocked ? '' : 'locked' }}">
                        @if($avatarUrl)<picture><img src="{{ $avatarUrl }}" alt=""></picture>@endif
                    </profile-picture>
                    @if($isOwner && $unlocked)
                        <gradient-button class="profilePictureSelectBtn">
                            <button class="custom_btn" onclick="toggleProfilePicture('{{ $machineName }}')"><span>{{ __('t_ingame.achievements.select') }}</span></button>
                        </gradient-button>
                        <gradient-button class="profilePictureDeselectBtn">
                            <button class="custom_btn" onclick="toggleProfilePicture('{{ $machineName }}')"><span>{{ __('t_ingame.achievements.deselect') }}</span></button>
                        </gradient-button>
                    @endif
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
                    $skinBase = 'img/achievements/skins/'.$machineName;
                    $skinUrl = null;
                    foreach (['.png', '.jpg'] as $ext) {
                        if (file_exists(public_path($skinBase.$ext))) { $skinUrl = '/'.$skinBase.$ext; break; }
                    }
                @endphp
                <div class="achievementOverviewSpaceObjectSkinHolder {{ $isCurrent ? 'selected' : '' }}"
                     data-space-object-skin-id="{{ $machineName }}">
                    <space-object-skin class="{{ $machineName }} {{ $unlocked ? '' : 'locked' }}">
                        @if($skinUrl)<img src="{{ $skinUrl }}" alt="">@endif
                    </space-object-skin>
                    @if($isOwner && $unlocked)
                        <gradient-button class="spaceObjectSkinSelectBtn">
                            <button class="custom_btn" onclick="toggleSpaceObjectSkin('{{ $machineName }}')"><span>{{ __('t_ingame.achievements.select') }}</span></button>
                        </gradient-button>
                        <gradient-button class="spaceObjectSkinDeselectBtn">
                            <button class="custom_btn" onclick="toggleSpaceObjectSkin('{{ $machineName }}')"><span>{{ __('t_ingame.achievements.deselect') }}</span></button>
                        </gradient-button>
                    @endif
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
                    $titleText = $titleTextLookup[$machineName] ?? $machineName;
                @endphp
                <div class="achievementOverviewProfileTitleHolder {{ $unlocked ? '' : 'locked' }} {{ $isCurrent ? 'selected' : '' }}"
                     data-title-id="{{ $machineName }}">
                    <div class="titleHolder" lang="it">{{ $titleText }}</div>
                    @if($isOwner && $unlocked)
                        <gradient-button class="profileTitleSelectBtn">
                            <button class="custom_btn" onclick="toggleProfileTitle('{{ $machineName }}')"><span>{{ __('t_ingame.achievements.select') }}</span></button>
                        </gradient-button>
                        <gradient-button class="profileTitleDeselectBtn">
                            <button class="custom_btn" onclick="toggleProfileTitle('{{ $machineName }}')"><span>{{ __('t_ingame.achievements.deselect') }}</span></button>
                        </gradient-button>
                    @endif
                </div>
            @endforeach
            @if(count($rewardCatalog['title']) === 0)
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endif
        </div>

    </div>

    {{-- ── Tooltip "Scegli un pianeta" per selezione skin (replica OGame) ── --}}
    @if($isOwner && !empty($playerSpaceObjects))
        <div id="spaceObjectSkinSelectTooltipBackdrop" style="display:none;" onclick="closeSpaceObjectSkinTooltip()"></div>
        <div id="spaceObjectSkinSelectTooltip" class="htmlTooltip" style="display:none;" data-pending-skin="">
            <h1>{{ __('t_ingame.achievements.choose_planet') }}</h1>
            <div class="splitLine"></div>
            @foreach($playerSpaceObjects as $pid => $obj)
                <div class="spaceObjectSkinSelectionSpaceObjectsPerCoordinate">
                    <div class="spaceObjectSkinSelectionCoordinatesHolder">{{ $obj['coordinates'] }}</div>
                    <div class="spaceObjectSkinSelectionSpaceObjectsHolder">
                        <div class="spaceObjectSkinSelectionSpaceObject" onclick="applyPendingSkinToPlanet({{ $pid }})">
                            <img class="planetPic spaceObjectImage_{{ $pid }}"
                                 alt="{{ $obj['name'] }}"
                                 src="{{ $obj['image'] }}"
                                 data-default-src="{{ $obj['defaultImage'] }}"
                                 width="25" height="25">
                            <span>{{ $obj['localizedSpaceObjectType'] }} - {{ $obj['name'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
