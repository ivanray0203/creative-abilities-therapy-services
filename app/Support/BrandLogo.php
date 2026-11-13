<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * The clinic logo (and other bundled images) as inline `data:` URIs, for the
 * email templates and the generated PDFs.
 *
 * Emails carried the logo as a hosted URL, which only renders when the
 * recipient's client can reach `APP_URL` — never true from an external inbox
 * pointed at a local install. Inlining the bytes removes that dependency.
 *
 * PDFs need it too: given a file path, dompdf hands the image to the Imagick
 * extension as a `file://` URL whenever Imagick is installed (as it is in
 * production), and Imagick cannot open that — the PDF fails to build. Given
 * a data URI, dompdf writes the bytes to a temp file and passes a plain path.
 *
 * Emails use the 192px file: the header renders it at 75px, and base64
 * inflates by roughly a third, so the larger file would add ~110KB to every
 * message for no visible gain. Letter-style PDFs print it larger and use 512.
 */
class BrandLogo
{
    /** @var array<string, string> Encoded once per process, keyed by absolute path. */
    private static array $dataUris = [];

    /** The logo at the given size (192 or 512), or '' when the file is missing. */
    public static function dataUri(int $size = 192): string
    {
        return self::inline(public_path("CatsLogo/web-app-manifest-{$size}x{$size}.png"));
    }

    /**
     * Any image file as a data URI, or '' when it does not exist.
     *
     * Encoded once per process — every render would otherwise re-read and
     * re-encode the same file.
     */
    public static function inline(string $absolutePath): string
    {
        if (array_key_exists($absolutePath, self::$dataUris)) {
            return self::$dataUris[$absolutePath];
        }

        if (! File::isFile($absolutePath)) {
            return self::$dataUris[$absolutePath] = '';
        }

        $mimeType = File::mimeType($absolutePath) ?: 'image/png';

        return self::$dataUris[$absolutePath] = "data:{$mimeType};base64,".base64_encode((string) File::get($absolutePath));
    }
}
