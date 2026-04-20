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
        @foreach ($categories as $categoryKey => $category)
            @php
                $containsOpen = false;
                foreach ($category['objects'] as $catObj) {
                    if ($catObj->id === $object->id) { $containsOpen = true; break; }
                }
            @endphp
            @if (empty($category['objects']))
                @continue
            @endif
            <h1>{{ __($category['label_key']) }}</h1>
            <ul data-category="{{ $categoryKey }}"{{ $containsOpen ? ' style=display:block' : '' }}>
                @foreach ($category['objects'] as $categoryObject)
                    @php($isOpen = $categoryObject->id === $object->id)
                    <li class="{{ $categoryObject->class_name }}{{ $isOpen ? ' open' : '' }}">
                        <a href="{{ route('techtree.ajax', ['tab' => 3, 'object_id' => $categoryObject->id]) }}"
                           class="technology sprite sprite_small small overlay {{ $categoryObject->class_name }} tooltipHTML"
                           data-overlay-same="true"
                           title="{{ $categoryObject->title }}|{{ $categoryObject->description }}">
                            {{ $categoryObject->title }}
                        </a>
                        @if ($isOpen)
                            @if (empty($open_requirements))
                                <a class="prerequisites"><span class="hint">{{ __('t_ingame.techtree.no_requirements') }}</span></a>
                            @else
                                @foreach ($open_requirements as $requirement)
                                    <a href="{{ route('techtree.ajax', ['tab' => 3, 'object_id' => $requirement->gameObject->id]) }}"
                                       class="prerequisites overlay sprite_small small {{ $requirement->gameObject->class_name }} {{ $requirement->levelCurrent >= $requirement->levelRequired ? 'fulfilled' : 'unfulfilled' }}"
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
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</div>

<script type="text/javascript">
    $(function(){
        initOverlayName();
        $('#technologytree .content.technologies > h1').off('click.techtree').on('click.techtree', function(){
            $(this).next('ul').slideToggle(150);
        });
    });
</script>
