<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\ProductionTask;
use App\Policies\OrderPolicy;
use App\Policies\ProductionTaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Order::class => OrderPolicy::class,
        ProductionTask::class => ProductionTaskPolicy::class,
    ];
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
