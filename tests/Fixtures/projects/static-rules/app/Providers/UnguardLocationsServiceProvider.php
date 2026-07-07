<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UnguardLocationsServiceProvider
{
    public function boot(): void
    {
        Model::unguard();

        User::unguard();
    }
}
