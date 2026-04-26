{{-- Alliance Classes Tab — replicates OGame ufficiale UI (s274-it) --}}
@php
    use OGame\Enums\AllianceClass;
    /** @var \OGame\Models\Alliance $alliance */
    /** @var ?AllianceClass $currentClass */
    /** @var int $changeCost */
    /** @var bool $freeAvailable */
    /** @var ?\Illuminate\Support\Carbon $freeAvailableAt */
    /** @var int $userDarkMatter */
    $classes = [AllianceClass::WARRIOR, AllianceClass::TRADER, AllianceClass::RESEARCHER];
@endphp
<div id="allianceclassselection">
    <div class="content">
        <h2>{{ __('t_ingame.alliance.select_class_title') }}</h2>
        <p>{{ __('t_ingame.alliance.select_class_note') }}</p>

        @if ($currentClass !== null)
            <p>
                <strong>{{ __('t_ingame.alliance.current_class') }}:</strong>
                {{ __('t_ingame.alliance.class_' . strtolower($currentClass->name)) }}
            </p>
        @endif

        <div class="allianceclass boxes">
            @foreach ($classes as $class)
                @php
                    $isActive = $currentClass === $class;
                    // Replica testo ufficiale OGame: "Acquista per:<br>500.000 MO" oppure "Attiva gratis"
                    if ($freeAvailable) {
                        $btnLabelHtml = e(__('t_ingame.alliance.activate_free'));
                    } else {
                        $btnLabelHtml = e(__('t_ingame.alliance.buy_for')) . ':<br>'
                            . number_format($changeCost, 0, ',', '.') . ' MO';
                    }
                    $insufficientDm = !$freeAvailable && $changeCost > $userDarkMatter;
                    $tooltip = '';
                    if (!$freeAvailable && !$alliance->alliance_class_free_used && $freeAvailableAt) {
                        $tooltip = __('t_ingame.alliance.free_available_at') . ': ' . $freeAvailableAt->format('Y-m-d H:i:s');
                    } elseif ($insufficientDm) {
                        $tooltip = __('t_ingame.alliance.no_dark_matter');
                    }
                @endphp
                <div class="allianceclass box {{ $isActive ? 'active' : '' }}"
                     data-alliance-class-id="{{ $class->value }}"
                     data-alliance-class-name="{{ __('t_ingame.alliance.class_' . strtolower($class->name)) }}"
                     data-alliance-class-price="{{ $changeCost }}">
                    <div class="buttons">
                        @if ($isActive)
                            <a class="build-it tooltip js_hideTipOnMobile" style="cursor:default;">
                                <span>{{ __('t_ingame.alliance.class_active') }}</span>
                            </a>
                        @elseif ($insufficientDm)
                            {{-- Stato disabilitato: stesso markup OGame ufficiale (build-it_disabled + nodarkmatter) --}}
                            <a class="build-it_disabled tooltip js_hideTipOnMobile nodarkmatter"
                               rel="{{ route('premium.index') }}"
                               @if ($tooltip) data-tooltip-title="{{ $tooltip }}" @endif>
                                <span>{!! $btnLabelHtml !!}</span>
                            </a>
                        @else
                            <form method="POST" action="{{ route('alliance.class.select') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="class_id" value="{{ $class->value }}">
                                <a class="build-it tooltip js_hideTipOnMobile"
                                   onclick="this.closest('form').submit(); return false;"
                                   @if ($tooltip) data-tooltip-title="{{ $tooltip }}" @endif>
                                    <span>{!! $btnLabelHtml !!}</span>
                                </a>
                            </form>
                        @endif
                    </div>
                    <div class="sprite allianceclass large {{ $class->getMachineName() }}"></div>
                    <div class="boxClassBoni">
                        <h2>{{ __('t_ingame.alliance.class_' . strtolower($class->name)) }}</h2>
                        <ul>
                            @foreach ($class->getBonusLangKeys() as $bonusKey)
                                <li class="allianceclass bonus">{{ __($bonusKey) }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>

        <br>
    </div>
</div>
