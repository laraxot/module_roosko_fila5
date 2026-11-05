<?php

declare(strict_types=1);

namespace Modules\Roosko\Providers;

use Modules\Xot\Providers\XotBaseRouteServiceProvider;

class RouteServiceProvider extends XotBaseRouteServiceProvider
{
    public string $name = 'Roosko';

    protected string $moduleNamespace = 'Modules\Roosko\Http\Controllers';

    protected string $module_dir = __DIR__;

    protected string $module_ns = __NAMESPACE__;
}
