<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;

class AllThenFilterController
{
    public function unsafe(): void
    {
        User::all()->filter(fn ($user) => $user->active);
        User::all()->where('active', true);
    }
}
