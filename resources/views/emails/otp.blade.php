<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #0b1220; margin-bottom: 20px;">Password Reset Request</h2>
        <p style="color: #444; font-size: 16px; line-height: 1.5;">You requested a password reset for your account. Please use the following One-Time Password (OTP) to proceed with resetting your password.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; padding: 15px 30px; background-color: #2563eb; color: #ffffff; font-size: 24px; font-weight: bold; border-radius: 6px; letter-spacing: 4px;">{{ $otp }}</span>
        </div>

        <p style="color: #444; font-size: 14px;">This OTP will expire in <strong>15 minutes</strong>. If you did not request a password reset, please ignore this email.</p>
        
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;" />
        <p style="color: #888; font-size: 12px; text-align: center;">&copy; {{ date('Y') }} Stock Inventory Management System. All rights reserved.</p>
    </div>
</body>
</html>
