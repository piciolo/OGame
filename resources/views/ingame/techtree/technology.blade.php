@php
    use OGame\GameObjects\Models\Abstracts\GameObject;
    use OGame\GameObjects\Models\Techtree\TechtreeRequirement;
    /** @var GameObject $object */
    /** @var array<TechtreeRequirement> $requirements */
@endphp

<div id="technologytree" data-title="{{ __('t_ingame.techtree.page_title') }} - {{ $object->title }}">
    @include('ingame.techtree.partials.nav', ['currentAction' => 'technologies', 'objectId' => $object->id])

    <div class="content technology">
        @if (empty($requirements))
            <p class="hint">
                {{ __('t_ingame.techtree.no_requirements') }}
            </p>
        @else
            <ul class="requirements">
                @foreach ($requirements as $requirement)
                    <li class="{{ $requirement->levelCurrent >= $requirement->levelRequired ? 'built' : 'notBuilt' }}">
                        <a href="{{ route('techtree.ajax', ['tab' => 1, 'object_id' => $requirement->gameObject->id]) }}"
                           class="sprite sprite_small small overlay {{ $requirement->gameObject->class_name }} tooltipHTML"
                           data-overlay-same="true"
                           title="{{ $requirement->gameObject->title }}|{{ $requirement->gameObject->description }}">
                        </a>
                        <span class="name">{{ $requirement->gameObject->title }}</span>
                        <span class="level">
                            {{ __('t_ingame.techtree.level') }}
                            @if ($requirement->levelCurrent >= $requirement->levelRequired)
                                {{ $requirement->levelRequired }}
                            @else
                                {{ $requirement->levelCurrent }} / {{ $requirement->levelRequired }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<script type="text/javascript">
    $(
        function(){
            initOverlayName();
        }
    );
</script>
