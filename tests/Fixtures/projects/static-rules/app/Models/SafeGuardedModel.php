<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafeGuardedModel extends Model
{
    protected $guarded = [];

    protected $fillable = ['name'];

    public function fillSafely(): void
    {
        Model::unguarded(fn () => $this->fill(['name' => 'safe']));
        Model::reguard();
    }
}
