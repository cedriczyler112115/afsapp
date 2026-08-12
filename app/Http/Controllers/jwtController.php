<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class jwtController extends Controller
{
    /**
     * Authenticate user from a JWT token passed as a path parameter.
     */
    public function token(Request $request, $token)
    {
        // 1. Get the shared encryption key
        $secret = env('_encryption_key_');
        if (!$secret) {
            // Robust parsing fallback for '_encryption_key_' => 'louiejaybulahan' format
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                if (preg_match("/'_encryption_key_'\s*=>\s*['\"]([^'\"]+)['\"]/", $envContent, $matches)) {
                    $secret = $matches[1];
                } elseif (preg_match("/_encryption_key_=(.*)/", $envContent, $matches)) {
                    $secret = trim($matches[1], "\"' ");
                }
            }
        }
        if (!$secret) {
            $secret = 'louiejaybulahan';
        }

        // 2. Decode and verify the JWT signature
        $payload = $this->verifyAndDecodeToken($token, $secret);

        if (!$payload) {
            Log::warning('jwt_auth_failed', [
                'token' => substr($token, 0, 20) . '...',
                'ip' => $request->ip(),
            ]);
            abort(403, 'Unauthorized: Invalid token signature.');
        }

        // 3. Extract user credentials from payload
        $email = $payload['email'] ?? $payload['username'] ?? null;
        if (!$email) {
            abort(403, 'Unauthorized: Missing email in token payload.');
        }

        // 4. Construct user name
        $name = trim(($payload['fname'] ?? '') . ' ' . ($payload['mname'] ?? '') . ' ' . ($payload['lname'] ?? ''));
        if (empty($name)) {
            $name = $payload['name'] ?? explode('@', $email)[0];
        }

        // 5. Look up or create the user in the local database
        $user = User::where('email', $email)->first();

        if ($user) {
            // Do not change or update the value of the existing user in the database
        } else {
            // Create user
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            if (isset($payload['password'])) {
                $user->setRawAttributes(array_merge($user->getAttributes(), [
                    'password' => $payload['password']
                ]));
            } else {
                $user->password = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16));
            }
            if (isset($payload['level_id'])) {
                $user->level_id = $payload['level_id'];
            }
            if (Schema::hasColumn('users', 'is_status')) {
                $user->is_status = 1;
            }
            $user->save();
        }

        // 6. Log the user in and regenerate session to secure the session
        Auth::login($user, true);
        $request->session()->regenerate();

        // 7. Redirect to dashboard, removing the JWT from the URL
        return redirect()->route('dashboard');
    }

    /**
     * Decode and verify a HS256 JWT token.
     */
    private function verifyAndDecodeToken(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // Verify signature using HMAC-SHA256
        $data = $headerB64 . '.' . $payloadB64;
        $expectedSig = hash_hmac('sha256', $data, $secret, true);
        
        // Base64URL encode computed signature
        $expectedSigB64 = str_replace('=', '', strtr(base64_encode($expectedSig), '+/', '-_'));

        if (!hash_equals($expectedSigB64, $signatureB64)) {
            return null;
        }

        // Decode payload
        $payloadJson = base64_decode(str_pad(strtr($payloadB64, '-_', '+/'), strlen($payloadB64) % 4, '=', STR_PAD_RIGHT));
        $payload = json_decode($payloadJson, true);

        return is_array($payload) ? $payload : null;
    }
}
