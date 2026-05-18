{{-- Renderizza una singola .profileEntry. Atteso $entry array. --}}
@if($entry['type'] === 'empty')
    <div class="profileEntry empty"></div>
@elseif($entry['type'] === 'title')
    @php
        // Issue #2: mostra titolo selezionato dal player se presente, altrimenti placeholder.
        $playerTitleText = trim((string) ($entry['title_text'] ?? ''));
        $titleLabel = $playerTitleText !== '' ? $playerTitleText : __('t_ingame.profile.no_title');
    @endphp
    <div class="profileEntry" data-type="{{ $entry['tag'] }}">
        <div class="profileHeading playerTitle">
            <span class="titleLabel {{ $playerTitleText !== '' ? 'hasTitle' : '' }}">{{ $titleLabel }}</span>
            <span class="titleSelector">
                <button type="button" class="custom_btn titleTypeSelection {{ ($entry['value'] ?? 'male') === 'male' ? 'active' : '' }}" data-gender="male" title="{{ __('t_ingame.profile.gender_male') }}">
                    <img src="/img/layout/gender_male.png" alt="M">
                </button>
                <button type="button" class="custom_btn titleTypeSelection {{ ($entry['value'] ?? 'male') === 'female' ? 'active' : '' }}" data-gender="female" title="{{ __('t_ingame.profile.gender_female') }}">
                    <img src="/img/layout/gender_female.png" alt="F">
                </button>
            </span>
        </div>
        <div class="profileValue"></div>
    </div>
@elseif($entry['type'] === 'simple')
    <div class="profileEntry" data-type="{{ $entry['tag'] }}">
        <div class="profileHeading">{{ $entry['label'] }}:</div>
        <div class="profileValue">
            <span>@if(is_int($entry['value'])){{ number_format($entry['value'], 0, ',', '.') }}@else{{ $entry['value'] }}@endif</span>
        </div>
        @if($entry['removable'] ?? true)
            <span class="emptyField removeEntry" title="{{ __('t_ingame.profile.remove_entry') }}">✗</span>
        @endif
    </div>
@elseif($entry['type'] === 'alliance')
    <div class="profileEntry" data-type="{{ $entry['tag'] }}">
        <div class="profileHeading">{{ $entry['label'] }}:</div>
        <div class="profileValue">
            @if($entry['value'] && !empty($entry['value']['tag']))
                <span class="allianceTag"><a href="#">[{{ $entry['value']['tag'] }}]</a></span>
                <span class="allianceName">{{ $entry['value']['name'] }}</span>
            @else
                <span class="middlemark">—</span>
            @endif
        </div>
        @if($entry['removable'] ?? true)
            <span class="emptyField removeEntry" title="{{ __('t_ingame.profile.remove_entry') }}">✗</span>
        @endif
    </div>
@elseif($entry['type'] === 'class')
    <div class="profileEntry" data-type="{{ $entry['tag'] }}">
        <div class="profileHeading">{{ $entry['label'] }}:</div>
        <div class="profileValue">
            <span class="characterclass-icon characterclass-icon-{{ $entry['value'] ?? 'none' }}"></span>
            <span>{{ $entry['value'] ? __('t_ingame.profile.class_'.$entry['value']) : __('t_ingame.profile.no_class') }}</span>
        </div>
        @if($entry['removable'] ?? true)
            <span class="emptyField removeEntry" title="{{ __('t_ingame.profile.remove_entry') }}">✗</span>
        @endif
    </div>
@elseif($entry['type'] === 'allianceClass')
    <div class="profileEntry" data-type="{{ $entry['tag'] }}">
        <div class="profileHeading">{{ $entry['label'] }}:</div>
        <div class="profileValue">
            <span class="allianceclass-icon allianceclass-{{ $entry['value']['machine'] ?? 'neutral' }} sq20"></span>
            <span>{{ $entry['value']['name'] ?? __('t_ingame.profile.no_alliance_class') }}</span>
        </div>
        @if($entry['removable'] ?? true)
            <span class="emptyField removeEntry" title="{{ __('t_ingame.profile.remove_entry') }}">✗</span>
        @endif
    </div>
@elseif($entry['type'] === 'highscore')
    <div class="profileEntry" data-type="{{ $entry['tag'] }}">
        <div class="profileHeading">{{ $entry['label'] }}:</div>
        <div class="profileValue highscoreValue">
            <div class="positionChangeField">
                <div>#{{ $entry['value']['rank'] > 0 ? $entry['value']['rank'] : '-' }}</div>
                <div class="change @if($entry['value']['change'] === 'up') arrowUp @elseif($entry['value']['change'] === 'down') arrowDown @else point @endif"></div>
                <div>{{ $entry['value']['delta'] !== 0 ? $entry['value']['delta'] : '' }}</div>
            </div>
            <div>{{ number_format($entry['value']['points'], 0, ',', '.') }}</div>
        </div>
        @if($entry['removable'] ?? true)
            <span class="emptyField removeEntry" title="{{ __('t_ingame.profile.remove_entry') }}">✗</span>
        @endif
    </div>
@endif
