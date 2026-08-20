<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * The clinic logo as an inline `data:` URI, for the email templates.
 *
 * Emails carried the logo as a hosted URL, which only renders when the
 * recipient's client can reach `APP_URL` — never true from an external inbox
 * pointed at a local install. Inlining the bytes removes that dependency.
 *
 * The 192px file is used rather than the 512px one: the header renders it at
 * 75px, and base64 inflates by roughly a third, so the larger file would add
 * ~110KB to every message for no visible gain.
 */
class BrandLogo
{
    private const PATH = 'CatsLogo/web-app-manifest-192x192.png';

    private static ?string $dataUri = null;

    /**
     * Encoded once per process — every mail render would otherwise re-read
     * and re-encode the same file.
     */
    public static function dataUri(): string
    {
        if (self::$dataUri !== null) {
            return self::$dataUri;
        }

        $path = public_path(self::PATH);

        if (! File::exists($path)) {
            return self::$dataUri = '';
        }

        return self::$dataUri = 'data:image/png;base64,'.base64_encode((string) File::get($path));
    }
}
