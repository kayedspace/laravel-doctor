<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class SslVerificationController
{
    public function unsafe(): array
    {
        return [
            'verify' => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ];
    }
}
