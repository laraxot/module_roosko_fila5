<?php

declare(strict_types=1);

namespace Modules\Roosko\Actions;

use Spatie\QueueableAction\QueueableAction;

/**
 * Coda configurata per un tipo di lavoro asincrono (`roosko.queues.{labels,rentri}`).
 */
class GetQueueNameAction
{
    use QueueableAction;

    public function execute(string $purpose): ?string
    {
        $queue = config('roosko.queues.'.$purpose);

        return \is_string($queue) && $queue !== '' ? $queue : null;
    }
}
