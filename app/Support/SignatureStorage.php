<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Decodes a base64 data-URL signature image and stores it on the public disk.
 */
class SignatureStorage
{
    private const MAX_BYTES = 1_048_576; // 1 MB

    /**
     * @return string the stored relative path (e.g. "signatures/ab12.png")
     */
    public static function storeDataUrl(string $dataUrl, string $prefix = 'sig'): string
    {
        if (! preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $dataUrl, $m)) {
            throw ValidationException::withMessages([
                'signature' => ['รูปแบบลายเซ็นไม่ถูกต้อง (รองรับเฉพาะ PNG/JPEG)'],
            ]);
        }

        $ext = $m[1] === 'jpg' ? 'jpeg' : $m[1];
        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode(strtr($base64, '-_', '+/'), true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'signature' => ['ไม่สามารถถอดรหัสลายเซ็นได้'],
            ]);
        }

        if (strlen($binary) > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'signature' => ['ขนาดลายเซ็นต้องไม่เกิน 1 MB'],
            ]);
        }

        $path = "signatures/{$prefix}-".Str::random(24).".{$ext}";
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
