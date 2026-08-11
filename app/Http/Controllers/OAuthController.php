<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use GuzzleHttp\Exception\ClientException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

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
                ->stateless()
                ->redirectUrl($this->googleRedirectUrl($request))
                ->with(['state' => 'desktop_' . $authKey])
                ->redirect();
        }

        $state = Crypt::encryptString(json_encode([
            'type' => 'web',
            'nonce' => Str::random(40),
            'issued_at' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));

        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($this->googleRedirectUrl($request))
            ->with(['state' => 'web_'.$state])
            ->redirect();
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
            } elseif ($state && str_starts_with($state, 'web_')) {
                $statePayload = json_decode(
                    Crypt::decryptString(substr($state, 4)),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $issuedAt = (int) ($statePayload['issued_at'] ?? 0);
                $isValidWebState = ($statePayload['type'] ?? null) === 'web'
                    && is_string($statePayload['nonce'] ?? null)
                    && strlen($statePayload['nonce']) >= 32
                    && $issuedAt > 0
                    && abs(now()->timestamp - $issuedAt) <= 600;

                if (! $isValidWebState) {
                    throw new InvalidStateException('Google login state is invalid or expired.');
                }
            } else {
                $desktopAuthKey = session('desktop_auth_key');
                if ($desktopAuthKey) {
                    $isDesktop = true;
                } else {
                    throw new InvalidStateException('Google login state is missing.');
                }
            }

            $googleUser = Socialite::driver('google')->stateless()->user();

            $userColumns = array_flip(Schema::getColumnListing('users'));
            if (! isset($userColumns['google_id'])) {
                throw new \RuntimeException('The users.google_id column is missing. Run the Google OAuth database migrations.');
            }

            // Some shared-hosting databases may still be on an older users schema.
            // Persist every Google profile field supported by that database while
            // keeping google_id as the only required OAuth linkage column.
            $googleProfile = [
                'google_id' => $googleUser->getId(),
            ];

            if (isset($userColumns['google_name'])) {
                $googleProfile['google_name'] = $googleUser->getName();
            }

            if (isset($userColumns['google_email'])) {
                $googleProfile['google_email'] = $googleUser->getEmail();
            }

            if (isset($userColumns['google_avatar'])) {
                $googleProfile['google_avatar'] = $googleUser->getAvatar();
            }

            // First try to find the user by their Google ID
            $user = User::where('google_id', $googleUser->getId())->first();

            // If not found by Google ID, fallback to finding by Email (to link legacy accounts)
            if (! $user) {
                $user = User::where('email', $googleUser->getEmail())->first();
            }

            if ($user) {
                $user->update($googleProfile);
            } else {
                $newUser = array_merge([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => null,
                ], $googleProfile);

                if (isset($userColumns['is_status'])) {
                    $newUser['is_status'] = 1;
                }

                $user = User::create($newUser);
            }

            // Do not force a remember token here. Legacy/imported users tables on
            // shared hosting may not have remember_token, while the normal session
            // is sufficient for completing Google authentication.
            Auth::login($user);

            if (isset($userColumns['is_status']) && (int) $user->is_status === 0) {
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

                $html = '<!DOCTYPE html>
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
</html>';

                return response($html, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Cache-Control' => 'no-store, private',
                ]);
            }

            return redirect()->route('dashboard');

        } catch (\Throwable $e) {
            $reference = Str::upper(Str::random(8));
            $googleError = $this->extractGoogleOAuthError($e);

            Log::error('Google OAuth callback failed', [
                'reference' => $reference,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'google_error' => $googleError,
                'redirect_uri' => config('services.google.redirect'),
                'request_url' => $request->fullUrlWithoutQuery(['code']),
            ]);

            $message = match (true) {
                $e instanceof InvalidStateException => 'Google login session expired or could not be verified. Please try again.',
                $googleError === 'redirect_uri_mismatch' => 'Google rejected the callback URL. Please verify the authorized redirect URI in Google Cloud Console and clear the config cache on Hostinger.',
                $googleError === 'invalid_client' => 'Google rejected the client credentials on the server. Please verify the Google Client ID and Secret on Hostinger.',
                $googleError === 'invalid_grant' => 'Google login code expired or became invalid. Please try again after clearing browser cookies and config cache.',
                $googleError === 'access_denied' => 'Google login was cancelled or access was denied.',
                str_contains(strtolower($e->getMessage()), 'access_denied') => 'Google login was cancelled or access was denied.',
                str_contains(strtolower($e->getMessage()), 'google oauth database migrations')
                    || str_contains(strtolower($e->getMessage()), 'unknown column')
                    || str_contains(strtolower($e->getMessage()), 'base table or view not found') => 'Google login database setup is incomplete. Please run the server migrations.',
                default => $googleError
                    ? "Unable to authenticate with Google ({$googleError}). Error reference: {$reference}"
                    : "Unable to authenticate with Google. Error reference: {$reference}",
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

    private function googleRedirectUrl(Request $request): string
    {
        return (string) config('services.google.redirect');
    }

    private function extractGoogleOAuthError(\Throwable $e): ?string
    {
        if ($e instanceof ClientException) {
            $response = $e->getResponse();
            if ($response) {
                $body = (string) $response->getBody();
                if ($body !== '') {
                    $decoded = json_decode($body, true);
                    if (is_array($decoded) && isset($decoded['error'])) {
                        return strtolower((string) $decoded['error']);
                    }

                    if (preg_match('/"error"\s*:\s*"([^"]+)"/i', $body, $matches)) {
                        return strtolower($matches[1]);
                    }
                }
            }
        }

        $message = strtolower($e->getMessage());

        foreach (['redirect_uri_mismatch', 'invalid_client', 'invalid_grant', 'access_denied'] as $needle) {
            if (str_contains($message, $needle)) {
                return $needle;
            }
        }

        return null;
    }
}
