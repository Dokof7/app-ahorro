<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Group::class => \App\Policies\GroupPolicy::class,
        \App\Models\Meeting::class => \App\Policies\MeetingPolicy::class,
        \App\Models\Member::class => \App\Policies\MemberPolicy::class,
        \App\Models\Loan::class => \App\Policies\LoanPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });
    }
}
