<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function generateJwt(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $headerB64 = str_replace('=', '', strtr(base64_encode(json_encode($header)), '+/', '-_'));
        $payloadB64 = str_replace('=', '', strtr(base64_encode(json_encode($payload)), '+/', '-_'));
        
        $data = $headerB64 . '.' . $payloadB64;
        $signature = hash_hmac('sha256', $data, $secret, true);
        $signatureB64 = str_replace('=', '', strtr(base64_encode($signature), '+/', '-_'));
        
        return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;
    }

    public function test_valid_jwt_authenticates_and_redirects_to_dashboard(): void
    {
        $payload = [
            'fname' => 'John',
            'mname' => 'Middle',
            'lname' => 'Doe',
            'email' => 'john.doe@example.com',
            'username' => 'john.doe@example.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // bcrypt 'password'
            'user_id' => 999,
            'level_id' => 60
        ];

        $secret = 'louiejaybulahan';

        $token = $this->generateJwt($payload, $secret);

        $response = $this->get('/jwt/token/' . $token);

        $response->assertRedirect(route('dashboard'));
        
        // Assert user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'name' => 'John Middle Doe',
            'level_id' => 60
        ]);

        // Assert user is authenticated
        $this->assertTrue(Auth::check());
        $this->assertEquals('john.doe@example.com', Auth::user()->email);
    }

    public function test_existing_user_attributes_are_not_changed(): void
    {
        $existingUser = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'john.doe@example.com',
            'level_id' => 10,
            'password' => 'original-password-hash',
        ]);

        $payload = [
            'fname' => 'John',
            'mname' => 'Middle',
            'lname' => 'Doe',
            'email' => 'john.doe@example.com',
            'username' => 'john.doe@example.com',
            'password' => 'new-password-hash',
            'user_id' => 999,
            'level_id' => 60
        ];

        $secret = 'louiejaybulahan';
        $token = $this->generateJwt($payload, $secret);

        $response = $this->get('/jwt/token/' . $token);

        $response->assertRedirect(route('dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($existingUser->id, Auth::user()->id);

        // Assert database was NOT updated
        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'john.doe@example.com',
            'name' => 'Original Name',
            'level_id' => 10,
            'password' => $existingUser->password,
        ]);
    }

    public function test_invalid_jwt_returns_403(): void
    {
        $payload = [
            'email' => 'hacker@example.com',
            'password' => 'some-hash'
        ];

        // Sign with wrong secret
        $token = $this->generateJwt($payload, 'wrong-secret');

        $response = $this->get('/jwt/token/' . $token);

        $response->assertStatus(403);
        $this->assertFalse(Auth::check());
    }

    public function test_missing_email_in_jwt_returns_403(): void
    {
        $payload = [
            'fname' => 'John',
            'password' => 'some-hash'
        ];

        $token = $this->generateJwt($payload, 'louiejaybulahan');

        $response = $this->get('/jwt/token/' . $token);

        $response->assertStatus(403);
        $this->assertFalse(Auth::check());
    }
}
