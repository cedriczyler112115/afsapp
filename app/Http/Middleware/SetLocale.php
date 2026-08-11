<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('app_locale')) {
            $request->session()->forget('app_locale');
        }

        App::setLocale('en');

        return $next($request);
    }
}
