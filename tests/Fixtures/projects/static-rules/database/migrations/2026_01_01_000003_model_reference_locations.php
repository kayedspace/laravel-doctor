<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->delete();

        Post::query()->delete();
    }
};
