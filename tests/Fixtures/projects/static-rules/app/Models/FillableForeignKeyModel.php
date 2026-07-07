<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FillableForeignKeyModel extends Model
{
    protected $fillable = ['team_id', 'name'];
}
