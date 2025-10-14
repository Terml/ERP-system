<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OrderService;
use App\Services\ProductionTaskService;
use App\Services\CompanyService;
use App\Services\ProductService;
use App\Services\UserService;
use App\Services\RoleService;
use App\Services\CacheService;
use App\Services\DocumentService;
use App\Models\Order;
use App\Models\ProductionTask;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use App\Observers\ProductObserver;
use App\Observers\OrderObserver;
use App\Observers\RoleObserver;
use App\Observers\CompanyObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->bind(OrderService::class, function ($app) {
        //     return new OrderService($app->make(Order::class));
        // });

        // $this->app->bind(ProductionTaskService::class, function ($app) {
        //     return new ProductionTaskService($app->make(ProductionTask::class));
        // });

        // $this->app->bind(CompanyService::class, function ($app) {
        //     return new CompanyService($app->make(Company::class));
        // });

        // $this->app->bind(ProductService::class, function ($app) {
        //     return new ProductService($app->make(Product::class));
        // });

        // $this->app->bind(UserService::class, function ($app) {
        //     return new UserService($app->make(User::class));
        // });

        // $this->app->bind(RoleService::class, function ($app) {
        //     return new RoleService($app->make(Role::class));
        // });
        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService($app->make(Order::class), $app->make(CacheService::class));
        });
        $this->app->singleton(ProductionTaskService::class, function ($app) {
            return new ProductionTaskService($app->make(ProductionTask::class));
        });
        $this->app->singleton(CompanyService::class, function ($app) {
            return new CompanyService($app->make(Company::class), $app->make(CacheService::class));
        });
        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService($app->make(Product::class), $app->make(CacheService::class));
        });
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService($app->make(User::class));
        });
        $this->app->singleton(RoleService::class, function ($app) {
            return new RoleService($app->make(Role::class), $app->make(CacheService::class));
        });
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });
        $this->app->singleton(DocumentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // автоматическая инвалидация кеша
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        Role::observe(RoleObserver::class);
        Company::observe(CompanyObserver::class);
    }
}
