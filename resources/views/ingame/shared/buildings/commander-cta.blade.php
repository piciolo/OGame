{{--
    "Buy Commander" call-to-action shown in the build slot of resource/facility
    icons when the player has no Commander officer and the building queue is at
    its 1-item cap. Mimics OGame's <a class="build-it_premium"> upsell.

    Click handler is wired in the host page (resources/index.blade.php and
    facilities/index.blade.php) and uses the legacy errorBoxDecision() dialog.
--}}
<div class="build-it_wrap">
    <a class="build-it_premium"
       href="javascript:void(0);"
       data-title=""
       data-url="{{ route('premium.index', ['openDetail' => 2]) }}"
       data-question="{{ __('t_ingame.ajax_object.commander_queue_info') }}">
        <span class="tooltip" data-tooltip-title="{{ __('t_ingame.buildings.commander_required_button') }}">{{ __('t_ingame.buildings.commander_required_button') }}</span>
    </a>
</div>
