{{--
    Panoramica direttive — replica fedele del markup OGame.
    Sorgente di riferimento: _research/extracted/ipioverview/2026-05-03_pilot/html/chapter-4001.html

    M2: render read-only. Stati hardcoded via IpiOverviewService::getTaskState():
        - task 5001 = "collected" (welcome auto-collected)
        - tutti gli altri = "none"
    Bottoni track/collect non operativi (verranno wired in M3).
--}}
<div id="ipioverviewlayer" class="ipiMainContent">
    <div id="ipiOverviewHeadbar">
        {{ __('t_ingame.ipi.overlay_title') }}
    </div>
    <div id="ipiOverviewContent">
        <ul id="ipiOverviewChapters">
            @foreach($allChapters as $c)
                <li class="ipiChapterItem btn_blue {{ $c->id === $chapter->id ? 'active' : '' }}">
                    <a href="{{ route('ipioverview.overlay', ['chapterId' => $c->id]) }}"
                       class="overlay ipiOverviewSelectChapter"
                       data-overlay-same="1">
                        {{ ['I','II','III','IV','V','VI','VII','VIII','IX','X'][$c->sort_order] ?? ($c->sort_order + 1) }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div id="ipiOverviewChapterTitle">
            {{ $service->localized($chapter, 'title') }}
        </div>

        @php $uid = auth()->id(); @endphp
        <div id="ipiOverviewTasklist">
            @foreach($chapter->tasks as $task)
                @php
                    $state = $service->getTaskState($task, $uid, $progressMap);
                    $progress = $service->getTaskProgress($task, $uid, $progressMap);
                    $isAuto = $task->id === 5001 || $state === 'tracked';
                @endphp
                <div class="ipiTaskItem {{ $isAuto && $state !== 'collected' ? 'active' : '' }}"
                     data-taskid="{{ $task->id }}"
                     data-state="{{ $state }}">
                    <div class="ipiTaskItemHeader">
                        <div class="ipiTaskItemTitle">
                            {{ $service->localized($task, 'title') }}
                            @if($state === 'collected')
                                <span class="ipiTaskCompletedMark">
                                    <img src="{{ $service->checkmarkUrl() }}" alt="">
                                </span>
                            @endif
                        </div>
                        <div class="ipiTaskItemProgress"
                             data-progress="{{ $progress }}"
                             data-total="{{ $task->total_steps }}">
                            {{ $progress }} / {{ $task->total_steps }}
                        </div>
                        <div class="ipiTaskItemTrack" data-target="{{ $task->id }}">
                            @switch($state)
                                @case('collected') {{ __('t_ingame.ipi.task_completed') }} @break
                                @case('completed') {{ __('t_ingame.ipi.collect_task') }} @break
                                @case('tracked')   {{ __('t_ingame.ipi.untrack_task') }} @break
                                @default           {{ __('t_ingame.ipi.track_task') }}
                            @endswitch
                        </div>
                    </div>

                    <div class="ipiTaskItemContent" @if($isAuto && $state !== 'collected') style="display:block" @endif>
                        <div class="ipiTaskItemContentInner">
                            <div class="ipiTaskItemDescription">
                                <div class="ipiTaskItemImage ipiImageTask{{ $task->id }}"
                                     style="background-image: url('{{ $service->imageUrl($task->image) }}');"></div>
                                <div class="ipiTaskItemDescriptionInner">
                                    {{ $service->localized($task, 'description') }}
                                </div>
                            </div>
                            <div class="ipiTaskItemContentSplit">
                                <div class="ipiTaskItemActions">
                                    {{ __('t_ingame.ipi.actions_to_complete') }}
                                    <ul class="ipiTaskItemActionsList">
                                        @foreach($task->actions as $action)
                                            <li class="ipiActionItem">
                                                {{ $service->localized($action, 'text') }}
                                                @if($service->isActionCompleted($action, $uid, $progressMap))
                                                    <span class="ipiActionCompletedMark">
                                                        <img src="{{ $service->checkmarkUrl() }}" alt="">
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="ipiTaskItemRewards">
                                    {{ __('t_ingame.ipi.task_rewards_label') }}
                                    <ul class="ipiRewardsList">
                                        @foreach($task->rewards as $r)
                                            <li class="ipiRewardItem">
                                                <span class="resourceReward resource-{{ $r->resource_index }} tooltip"
                                                      title="{{ $service->resourceLabel($r->resource_index) }}: {{ number_format($r->quantity, 0, ',', '.') }}">{{ number_format($r->quantity, 0, ',', '.') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="ipiTaskItemContentCollect">
                                <a class="claimTaskRewards {{ $state !== 'completed' ? 'disabled' : '' }} ipiOverviewCollectRewards"
                                   data-target="{{ $task->id }}">
                                    @switch($state)
                                        @case('collected') {{ __('t_ingame.ipi.task_completed') }} @break
                                        @default           {{ __('t_ingame.ipi.collect_task') }}
                                    @endswitch
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="ipiOverviewChapterRewards">
            <div>
                <span>{{ __('t_ingame.ipi.chapter_rewards_label') }}</span>
                <ul class="ipiRewardsList">
                    @foreach($chapter->rewards as $r)
                        <li class="ipiRewardItem">
                            <span class="resourceReward resource-{{ $r->resource_index }} tooltip"
                                  title="{{ $service->resourceLabel($r->resource_index) }}: {{ number_format($r->quantity, 0, ',', '.') }}">{{ number_format($r->quantity, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @php $chapterCollectable = $service->isChapterCollectable($chapter, $uid, $progressMap); @endphp
            <div>
                <a class="claimRewards {{ $chapterCollectable ? '' : 'disabled' }} ipiOverviewCollectRewards" data-target="{{ $chapter->id }}">
                    {{ __('t_ingame.ipi.collect_chapter') }}
                </a>
            </div>
        </div>

        <div class="og-loading">
            <div class="og-loading-overlay">
                <div class="og-loading-indicator"></div>
            </div>
        </div>
    </div>
    <div id="ipiOverviewFooter"></div>
</div>

<script>
    // The OGame-original IPI module appends "&token=" + token + "&taskId=" to these URLs,
    // so each must already terminate with at least one query parameter.
    var token = '{{ csrf_token() }}';
    IPI.initIpiLayer({
        trackTaskUrl:      "{{ route('ipioverview.tracktask') }}?ajax=1",
        collectTaskUrl:    "{{ route('ipioverview.collecttask') }}?ajax=1",
        collectChapterUrl: "{{ route('ipioverview.collectchapter') }}?ajax=1",
        loca: {
            LOCA_IPI_TRACK_TASK:        @json(__('t_ingame.ipi.track_task')),
            LOCA_IPI_UNTRACK_TASK:      @json(__('t_ingame.ipi.untrack_task')),
            LOCA_IPI_TASK_COLLECTED:    @json(__('t_ingame.ipi.task_completed')),
            LOCA_IPI_CHAPTER_COLLECTED: @json(__('t_ingame.ipi.chapter_completed'))
        }
    });
    if (typeof initTooltips === 'function') initTooltips();
</script>
