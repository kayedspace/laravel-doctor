<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Facades;

use Illuminate\Support\Facades\Facade;
use kayedspace\Doctor\DoctorManager;

class Doctor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DoctorManager::class;
    }
}
