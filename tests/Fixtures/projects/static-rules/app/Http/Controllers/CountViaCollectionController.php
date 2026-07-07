<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;

class CountViaCollectionController
{
    public function unsafe(): int
    {
        return User::query()->get()->count();
    }
}
