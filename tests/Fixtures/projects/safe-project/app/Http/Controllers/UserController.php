<?php

namespace App\Http\Controllers;

class UserController
{
    public function index()
    {
        // Unsafe: calling env() outside config file!
        $apiKey = env('API_KEY');

        return $apiKey;
    }
}
