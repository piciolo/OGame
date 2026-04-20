@php
    use OGame\GameObjects\Models\Abstracts\GameObject;
    use OGame\GameObjects\Models\Techtree\TechtreeRequirement;
    /** @var GameObject $object */
    /** @var array<string, array{label_key: string, objects: array<GameObject>}> $categories */
    /** @var array<TechtreeRequirement> $open_requirements */
@endphp

<div id="technologytree" data-title="{{ __('t_ingame.techtree.page_title') }} - {{ $object->title }}">
    @include('ingame.techtree.partials.nav', ['currentAction' => 'technologies', 'objectId' => $object->id])

    <div class="content technologies">
        @foreach ($categories as $category)
            @if (empty($category['objects']))
                @continue
            @endif
            <h1>{{ __($category['label_key']) }}</h1>
            <ul class="technologies">
                @foreach ($category['objects'] as $categoryObject)
                    @php($isOpen = $categoryObject->id === $object->id)
                    <li class="{{ $categoryObject->class_name }}{{ $isOpen ? ' open' : '' }}">
                        <a href="{{ route('techtree.ajax', ['tab' => 3, 'object_id' => $categoryObject->id]) }}"
                           class="sprite sprite_small small overlay {{ $categoryObject->class_name }} tooltipHTML"
                           data-overlay-same="true"
                           title="{{ $categoryObject->title }}|{{ $categoryObject->description }}">
                        </a>
                        <a href="{{ route('techtree.ajax', ['tab' => 3, 'object_id' => $categoryObject->id]) }}"
                           class="name overlay"
                           data-overlay-same="true">
                            {{ $categoryObject->title }}
                        </a>
                        @if ($isOpen)
                            <div class="requirements">
                                @if (empty($open_requirements))
                                    <span class="hint">{{ __('t_ingame.techtree.no_requirements') }}</span>
                                @else
                                    @foreach ($open_requirements as $requirement)
                                        <a href="{{ route('techtree.ajax', ['tab' => 3, 'object_id' => $requirement->gameObject->id]) }}"
                                           class="overlay {{ $requirement->levelCurrent >= $requirement->levelRequired ? 'built' : 'notBuilt' }}"
                                           data-overlay-same="true">
                                            {{ $requirement->gameObject->title }}
                                            ({{ __('t_ingame.techtree.level') }}
                                            @if ($requirement->levelCurrent >= $requirement->levelRequired)
                                                {{ $requirement->levelRequired }}
                                            @else
                                                {{ $requirement->levelCurrent }}/{{ $requirement->levelRequired }}
                                            @endif
                                            )
                                        </a>
                                    @endforeach
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</div>

<script type="text/javascript">
    $(
        function(){
            initOverlayName();
        }
    );
</script>
