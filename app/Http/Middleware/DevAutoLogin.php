<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DevAutoLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Berlapis: harus mode debug, environment lokal, DAN flag menyala.
        // Ketiganya sengaja digabung supaya tidak mungkin aktif di produksi.
        $enabled = config('app.debug')
            && app()->environment('local')
            && env('DEV_AUTO_LOGIN', false);

        if ($enabled && ! Auth::check()) {
            $user = User::where('email', env('DEV_AUTO_LOGIN_EMAIL', 'admin.ho@ptpn1.test'))->first();

            if ($user) {
                Auth::login($user);
            }
        }

        return $next($request);
    }
}