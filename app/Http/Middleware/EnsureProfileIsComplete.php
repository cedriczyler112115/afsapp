<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Let guests, logout, and profile-related routes pass freely
        if (!$user || $request->routeIs('profile.*') || $request->routeIs('logout') || $request->routeIs('register.*')) {
            return $next($request);
        }

        $isIncomplete = false;

        if (!$user->level_id || !$user->division_id || !$user->section_id) {
            $isIncomplete = true;
        } else {
            $sectionId = (int) $user->section_id;

            if (in_array($sectionId, [59, 61], true) && empty($user->province)) {
                $isIncomplete = true;
            }
            if ($sectionId === 60 && empty($user->cluster)) {
                $isIncomplete = true;
            }
            if ($sectionId === 61 && empty($user->municipality)) {
                $isIncomplete = true;
            }
        }

        if ($isIncomplete) {
            // Flash a message only if not already on the page
            return redirect()->route('profile.edit')->with('status', 'Please complete your profile to continue using the system.');
        }

        return $next($request);
    }
}
