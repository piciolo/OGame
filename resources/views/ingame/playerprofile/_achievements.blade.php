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
                @php $unlocked = in_array($machineName, $unlockedAvatars, true); @endphp
                <div class="achievementOverviewProfilePictureHolder {{ $unlocked ? 'unlocked' : '' }}"
                     data-machine-name="{{ $machineName }}">
                    <span class="profile-picture {{ $machineName }} {{ $unlocked ? 'unlocked' : 'locked' }}"></span>
                </div>
            @endforeach
            @if(count($rewardCatalog['avatar']) === 0)
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endif
        </div>

        {{-- ── Skin pianeta ─────────────────────────────────────────────── --}}
        <div id="achievementContentList_spaceObjectSkins" class="achievementContentList spaceObjectSkins hidden">
            @foreach($rewardCatalog['skin'] as $machineName)
                @php $unlocked = in_array($machineName, $unlockedSkins, true); @endphp
                <div class="achievementOverviewSpaceObjectSkinHolder {{ $unlocked ? 'unlocked' : '' }}"
                     data-machine-name="{{ $machineName }}">
                    <span class="space-object-skin {{ $machineName }} {{ $unlocked ? 'unlocked' : 'locked' }}"></span>
                </div>
            @endforeach
            @if(count($rewardCatalog['skin']) === 0)
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endif
        </div>

        {{-- ── Titoli ──────────────────────────────────────────────────── --}}
        <div id="achievementContentList_titles" class="achievementContentList titles hidden">
            @foreach($rewardCatalog['title'] as $machineName)
                @php $unlocked = in_array($machineName, $unlockedTitles, true); @endphp
                <div class="achievementOverviewTitleHolder {{ $unlocked ? 'unlocked' : 'locked' }}"
                     data-machine-name="{{ $machineName }}">
                    <span>{{ __('t_ingame.achievements.title_'.$machineName, [], __('t_ingame.profile.no_title')) }}</span>
                </div>
            @endforeach
            @if(count($rewardCatalog['title']) === 0)
                <div class="achievementsEmpty">{{ __('t_ingame.achievements.no_data') }}</div>
            @endif
        </div>

    </div>
</div>
