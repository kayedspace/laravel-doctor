<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class RequestFlowSecurityController
{
    public function unsafe($request): void
    {
        redirect()->to(request('next'));
        view($request->input('template'));
        file_get_contents(request('path'));
        $token = $request->input('token');
        md5($token);
    }
}
