@php
    use OGame\GameObjects\Models\Abstracts\GameObject;
    use OGame\GameObjects\Models\Techtree\TechtreeRequirement;
    /** @var GameObject $object */
    /** @var array<string, array{label_key: string, objects: array<GameObject>}> $categories */
    /** @var array<TechtreeRequirement> $open_requirements */
@endphp

<div id="technologytree" data-title="{{ __('t_ingame.techtree.page_title') }} - {{ $object->title }}">
    @include('ingame.techtree.partials.nav', ['currentAction' => 'technologies', 'objectId' => $object->id])

    <style>
        #technologytree .content.technologies a.technology .techicon,
        #technologytree .content.technologies a.prerequisites .techicon {
            position: absolute;
            left: 5px;
            top: 50%;
            width: 28px;
            height: 28px;
            margin-top: -14px;
            background-size: 28px 28px;
            background-repeat: no-repeat;
            background-position: center center;
        }
        #technologytree .content.technologies a.technology::before,
        #technologytree .content.technologies a.prerequisites::before {
            display: none !important;
        }
        #technologytree .content.technologies > ul > li .prerequisites-list {
            display: flex;
            flex-direction: column;
            width: calc(50% - 5px);
            gap: 4px;
            margin: 5px 0;
        }
        #technologytree .content.technologies > ul > li .prerequisites-list a.prerequisites {
            width: 100%;
            margin: 0;
        }
    </style>

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
                           class="technology overlay tooltipHTML"
                           data-overlay-same="true"
                           title="{{ $categoryObject->title }}|{{ $categoryObject->description }}">
                            <span class="techicon sprite sprite_small {{ $categoryObject->class_name }}"></span>{{ $categoryObject->title }}
                        </a>
                        @if ($isOpen)
                            @if (empty($open_requirements))
                                <span class="hint">{{ __('t_ingame.techtree.no_requirements') }}</span>
                            @else
                                <div class="prerequisites-list">
                                    @foreach ($open_requirements as $requirement)
                                        <a href="{{ route('techtree.ajax', ['tab' => 3, 'object_id' => $requirement->gameObject->id]) }}"
                                           class="prerequisites overlay {{ $requirement->levelCurrent >= $requirement->levelRequired ? 'fulfilled' : 'unfulfilled' }}"
                                           data-overlay-same="true">
                                            <span class="techicon sprite sprite_small {{ $requirement->gameObject->class_name }}"></span>{{ $requirement->gameObject->title }}
                                            ({{ __('t_ingame.techtree.level') }}
                                            @if ($requirement->levelCurrent >= $requirement->levelRequired)
                                                {{ $requirement->levelRequired }}
                                            @else
                                                {{ $requirement->levelCurrent }}/{{ $requirement->levelRequired }}
                                            @endif
                                            )
                                        </a>
                                    @endforeach
                                </div>
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
