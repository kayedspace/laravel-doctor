<?php

declare(strict_types=1);

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->where('active', true)->update(['active' => false]);

        Post::query()->delete();

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('active')->default(true);
        });
    }
};
