<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            Log::error('Google OAuth credentials are missing from the application configuration.');

            return redirect()->route('login')->withErrors([
                'email' => 'Google login is not configured on this server. Please contact the administrator.',
            ], 'login');
        }

        $authKey = $request->query('auth_key');
        if ($authKey) {
            session(['desktop_auth_key' => $authKey]);
            return Socialite::driver('google')
                ->with(['state' => 'desktop_' . $authKey])
                ->redirect();
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $state = $request->query('state');
            $isDesktop = false;
            $desktopAuthKey = null;

            if ($state && strpos($state, 'desktop_') === 0) {
                $isDesktop = true;
                $desktopAuthKey = substr($state, 8);
            } else {
                $desktopAuthKey = session('desktop_auth_key');
                if ($desktopAuthKey) {
                    $isDesktop = true;
                }
            }

            $driver = Socialite::driver('google');
            if ($isDesktop) {
                $driver = $driver->stateless();
            }

            $googleUser = $driver->user();

            // First try to find the user by their Google ID
            $user = User::where('google_id', $googleUser->getId())->first();

            // If not found by Google ID, fallback to finding by Email (to link legacy accounts)
            if (! $user) {
                $user = User::where('email', $googleUser->getEmail())->first();
            }

            if ($user) {
                // Update existing user with google_id and other details
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'google_name' => $googleUser->getName(),
                    'google_email' => $googleUser->getEmail(),
                ]);
            } else {
                // Create a new user with default 'approve' fields
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'google_name' => $googleUser->getName(),
                    'google_email' => $googleUser->getEmail(),
                    'is_status' => 1, // 1 = approve
                    'password' => null, // Oauth user
                ]);
            }

            Auth::login($user, true);

            if ((int) $user->is_status === 0) {
                Auth::logout();
                return redirect()->route('login')->withErrors(['email' => 'Your account is pending approval. Please wait for activation or configure your organization details.'], 'login');
            }

            // Check if this login originated from Tauri desktop app via desktop_auth_key
            if ($isDesktop && $desktopAuthKey) {
                session()->forget('desktop_auth_key');

                $userPayload = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'google_name' => $user->google_name,
                    'google_email' => $user->google_email,
                    'is_status' => $user->is_status,
                ];

                // Cache for 5 minutes (300 seconds)
                Cache::put('google_auth_'.$desktopAuthKey, $userPayload, 300);

                return response()->html('<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Google Login Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 36px; text-align: center; max-width: 440px; }
        .icon-box { width: 64px; height: 64px; background: rgba(34, 197, 94, 0.2); color: #22c55e; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 20px; font-size: 32px; }
    </style>
</head>
<body>
    <div class="card shadow-lg">
        <div class="icon-box">✓</div>
        <h3 class="fw-bold mb-2">Google Authentication Successful!</h3>
        <p class="text-secondary small mb-4">Logged in as <strong>'.e($user->email).'</strong>. You may now close this browser tab and return to the AFS Desktop Application.</p>
        <button onclick="window.close()" class="btn btn-primary w-100">Close Browser Tab</button>
    </div>
</body>
</html>');
            }

            return redirect()->route('dashboard');

        } catch (\Throwable $e) {
            $reference = Str::upper(Str::random(8));

            Log::error('Google OAuth callback failed', [
                'reference' => $reference,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'redirect_uri' => config('services.google.redirect'),
                'request_url' => $request->fullUrlWithoutQuery(['code']),
            ]);

            $message = match (true) {
                str_contains(strtolower($e->getMessage()), 'invalid_state') => 'Google login session expired or could not be verified. Please try again.',
                str_contains(strtolower($e->getMessage()), 'access_denied') => 'Google login was cancelled or access was denied.',
                default => "Unable to authenticate with Google. Error reference: {$reference}",
            };

            return redirect()->route('login')->withErrors(['email' => $message], 'login');
        }
    }

    /**
     * Endpoint polled by Tauri desktop app to check for completed Google OAuth login.
     */
    public function checkGoogleAuth(Request $request)
    {
        $authKey = $request->query('auth_key');

        if (! $authKey) {
            return response()->json(['success' => false, 'message' => 'Missing auth_key parameter'], 400);
        }

        $cachedUser = Cache::get('google_auth_'.$authKey);

        if ($cachedUser) {
            return response()->json([
                'success' => true,
                'user' => $cachedUser,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Pending browser authentication...',
        ]);
    }
}
