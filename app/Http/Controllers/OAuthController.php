<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // First try to find the user by their Google ID
            $user = User::where('google_id', $googleUser->getId())->first();

            // If not found by Google ID, fallback to finding by Email (to link legacy accounts)
            if (!$user) {
                $user = User::where('email', $googleUser->getEmail())->first();
            }

            if ($user) {
                // Update existing user with google_id and other details
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'google_name' => $googleUser->getName(),
                    'google_email' => $googleUser->getEmail(),
                ]);
            }
            else {
                // Create a new user with default 'approve' fields as per System Requirements
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

            if ((int)$user->is_status === 0) {
                Auth::logout();
                return redirect()->route('login')->withErrors(['email' => 'Your account is pending approval. Please wait for activation or configure your organization details.'], 'login');
            }

            return redirect()->route('dashboard');

        }
        catch (\Exception $e) {
            Log::error('Google OAuth Error', ['error' => $e->getMessage()]);
            return redirect()->route('login')->withErrors(['email' => 'Unable to authenticate with Google. Please try again.'], 'login');
        }
    }
}
