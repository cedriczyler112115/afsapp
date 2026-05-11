<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OtpRecoveryController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $email = $request->email;
        $otp = sprintf('%06d', mt_rand(100000, 999999));
        
        // Remove old OTPs for this email
        DB::table('password_reset_otps')->where('email', $email)->delete();

        // Insert new OTP
        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new PasswordResetOtp($otp));
        } catch (\Exception $e) {
            Log::error('Mail OTP Failed: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Failed to send OTP email. Attempting config issue.']);
        }

        return redirect()->route('password.otp.verify', ['email' => $email])
                         ->with('status', 'An OTP has been sent to your email.');
    }

    public function showVerifyForm(Request $request)
    {
        return view('auth.verify-otp', ['email' => $request->query('email')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6'
        ]);

        /** @var object|null $record */
        $record = DB::table('password_reset_otps')->where('email', $request->email)->first();

        if (!$record || $record->otp !== $request->otp) {
            return back()->withErrors(['otp' => 'The OTP entered is incorrect. Please try again.']);
        }

        if (now()->greaterThan($record->expires_at)) {
            return back()->withErrors(['otp' => 'The OTP has expired. Please request a new one.']);
        }

        return redirect()->route('password.reset.form', ['email' => $request->email, 'otp' => $request->otp]);
    }

    public function showResetForm(Request $request)
    {
        return view('auth.reset-password', [
            'email' => $request->query('email'),
            'otp' => $request->query('otp')
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|confirmed|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
        ], [
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.'
        ]);

        /** @var object|null $record */
        $record = DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record || now()->greaterThan($record->expires_at)) {
            return redirect()->route('password.request')->withErrors(['email' => 'Invalid or expired OTP session. Please try again.']);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_otps')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Your password has been reset successfully. You can now log in.');
    }
}
