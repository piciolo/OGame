<?php

namespace OGame\ViewModels\Queue\Abstracts;

class QueueListViewModel
{
    /**
     * Constructor.
     *
     * @param array<QueueViewModel> $queue
     */
    public function __construct(
        /**
         * List of queue items.
         */
        public array $queue
    ) {
    }

    /**
     * Get amount of items in the queue.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->queue);
    }

    /**
     * Whether the queue has reached the maximum allowed items.
     *
     * @param int|null $maxItems Optional override for the max queue size. Defaults to 5
     *                           (1 currently building + 4 in queue), the Commander-officer cap.
     * @return bool
     */
    public function isQueueFull(int|null $maxItems = null): bool
    {
        $maxItemsInQueue = $maxItems ?? 5;
        return count($this->queue) >= $maxItemsInQueue;
    }
}
