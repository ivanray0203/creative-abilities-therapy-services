<?php

/**
 * The brand palette lives as bare HSL triplets in `resources/css/app.css`
 * (Tailwind consumes them through `hsl(var(--token))`), which makes it easy
 * for an edit to drift a token a few percent off-brand without anyone
 * noticing. These tests convert the declared triplets back to hex and pin
 * them to the two brand colours.
 */
const BRAND_ORANGE = '#D87E45';

const BRAND_DARK = '#4D4D4D';

/**
 * Convert a CSS `H S% L%` triplet to an uppercase hex string.
 */
function hslTripletToHex(string $triplet): string
{
    [$hue, $saturation, $lightness] = array_map(
        static fn (string $part): float => (float) rtrim(trim($part), '%'),
        preg_split('/[\s,]+/', trim($triplet), -1, PREG_SPLIT_NO_EMPTY),
    );

    $saturation /= 100;
    $lightness /= 100;

    $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
    $second = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
    $match = $lightness - $chroma / 2;

    $channels = match ((int) floor($hue / 60) % 6) {
        0 => [$chroma, $second, 0],
        1 => [$second, $chroma, 0],
        2 => [0, $chroma, $second],
        3 => [0, $second, $chroma],
        4 => [$second, 0, $chroma],
        default => [$chroma, 0, $second],
    };

    return '#'.implode('', array_map(
        static fn (float $channel): string => str_pad(
            strtoupper(dechex((int) round(($channel + $match) * 255))), 2, '0', STR_PAD_LEFT,
        ),
        $channels,
    ));
}

/**
 * @return array<int, string>
 */
function declaredValuesFor(string $token): array
{
    preg_match_all(
        '/'.preg_quote($token, '/').':\s*([^;]+);/',
        file_get_contents(resource_path('css/app.css')),
        $matches,
    );

    expect($matches[1])->not->toBeEmpty("`{$token}` is not declared in app.css");

    return $matches[1];
}

test('the hsl to hex helper matches known conversions', function () {
    expect(hslTripletToHex('0 0% 100%'))->toBe('#FFFFFF')
        ->and(hslTripletToHex('0 0% 0%'))->toBe('#000000')
        ->and(hslTripletToHex('0 100% 50%'))->toBe('#FF0000')
        ->and(hslTripletToHex('120 100% 50%'))->toBe('#00FF00')
        ->and(hslTripletToHex('240 100% 50%'))->toBe('#0000FF');
});

test('every brand orange token resolves to the brand orange', function (string $token) {
    foreach (declaredValuesFor($token) as $value) {
        expect(hslTripletToHex($value))->toBe(BRAND_ORANGE);
    }
})->with([
    '--primary',
    '--ring',
]);

test('every brand dark token resolves to the brand dark', function (string $token) {
    foreach (declaredValuesFor($token) as $value) {
        expect(hslTripletToHex($value))->toBe(BRAND_DARK);
    }
})->with([
    '--accent',
]);

test('the tailwind brand utilities resolve to the brand colours', function (string $token, string $expected) {
    foreach (declaredValuesFor($token) as $value) {
        expect(hslTripletToHex(trim(str_replace(['hsl(', ')'], '', $value))))->toBe($expected);
    }
})->with([
    ['--color-primary-orange', BRAND_ORANGE],
    ['--color-charcoal-gray', BRAND_DARK],
]);

test('the inertia progress bar uses the brand orange', function () {
    expect(file_get_contents(resource_path('js/app.tsx')))
        ->toContain("color: '".BRAND_ORANGE."'");
});

test('the invoice pdfs accent with the brand orange', function (string $template) {
    $styles = file_get_contents(resource_path("views/pdf/{$template}.blade.php"));

    expect($styles)->toContain(BRAND_ORANGE)
        ->and($styles)->not->toContain('#E2853D');
})->with([
    'invoice',
    'therapist-invoice',
]);
