<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your KENHAVATE Login Code</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #2563eb; margin: 0;">KENHAVATE</h1>
        <p style="color: #6b7280; margin: 5px 0;">Kenya National Highways Authority</p>
    </div>

    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 30px; margin-bottom: 30px;">
        <h2 style="color: #1f2937; margin-top: 0; margin-bottom: 20px;">Your Login Code</h2>

        <p style="margin-bottom: 20px;">Hello,</p>

        <p style="margin-bottom: 30px;">You requested to log in to your KENHAVATE account. Use the following one-time password (OTP) to complete your login:</p>

        <div style="text-align: center; margin: 30px 0;">
            <div style="display: inline-block; background-color: #2563eb; color: white; font-size: 32px; font-weight: bold; padding: 20px 40px; border-radius: 8px; letter-spacing: 4px;">
                {{ $otp }}
            </div>
        </div>

        <div style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
            <p style="margin: 0; color: #92400e; font-weight: 500;">
                <strong>Security Notice:</strong> This code will expire in {{ config('kenhavate.otp.expiry_minutes', 10) }} minutes and can only be used once.
            </p>
        </div>

        <p style="margin-bottom: 10px;">If you didn't request this login, please ignore this email.</p>

        <p style="margin-bottom: 0;">For security reasons, never share this code with anyone.</p>
    </div>

    <div style="text-align: center; color: #6b7280; font-size: 14px;">
        <p>This is an automated message from KENHAVATE. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} Kenya National Highways Authority. All rights reserved.</p>
    </div>
</body>
</html>