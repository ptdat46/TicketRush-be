<?php

namespace App\Services;

use App\Models\VerificationCode;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;

class VerificationCodeService
{
    /**
     * Generate a 6-digit numeric code.
     */
    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send a verification code to the given email.
     * Deletes any existing unexpired codes of the same type for this email first.
     */
    public function sendCode(string $email, string $type = 'register'): VerificationCode
    {
        $this->deleteExistingCodes($email, $type);

        $code = $this->generateCode();

        $verificationCode = VerificationCode::create([
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($email)->send(new VerificationCodeMail($code));

        return $verificationCode;
    }

    /**
     * Verify the code for the given email and type.
     * Returns true if valid, false otherwise. Deletes the code after successful verification.
     */
    public function verifyCode(string $email, string $code, string $type = 'register'): bool
    {
        $record = VerificationCode::where('email', $email)
            ->where('code', $code)
            ->where('type', $type)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return false;
        }

        $record->delete();

        return true;
    }

    /**
     * Delete all existing codes for the given email and type.
     */
    public function deleteExistingCodes(string $email, string $type = 'register'): void
    {
        VerificationCode::where('email', $email)
            ->where('type', $type)
            ->delete();
    }
}
