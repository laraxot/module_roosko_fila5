<?php

declare(strict_types=1);

namespace Modules\Roosko\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;

class EventServiceProvider extends BaseEventServiceProvider
{
    /** @var array<string, array<int, string>> */
    protected $listen = [];

    /** @var bool */
    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
