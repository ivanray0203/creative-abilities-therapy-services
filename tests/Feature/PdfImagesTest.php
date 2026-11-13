<?php

use App\Models\Client;
use App\Models\Intake;
use App\Support\BrandLogo;
use Illuminate\Support\Facades\File;

/*
 * Given a file path, dompdf passes images to the Imagick extension as a
 * `file://` URL, which Imagick can't open — so every image in a generated
 * PDF is inlined as a data URI instead (see BrandLogo).
 */

test('the logo is inlined as a data uri at both sizes', function () {
    expect(BrandLogo::dataUri())->toStartWith('data:image/png;base64,')
        ->and(BrandLogo::dataUri(512))->toStartWith('data:image/png;base64,')
        ->and(BrandLogo::dataUri(512))->not->toBe(BrandLogo::dataUri());
});

test('a missing image inlines as an empty string', function () {
    expect(BrandLogo::inline(public_path('CatsLogo/does-not-exist.png')))->toBe('');
});

test('no pdf template loads an image from a file path', function () {
    foreach (File::files(resource_path('views/pdf')) as $template) {
        $source = File::get($template->getPathname());

        expect($source)->not->toContain('public_path(')
            ->and($source)->not->toContain('logoPath');
    }
});

test('the consent pdf embeds its logo rather than pointing at the file', function () {
    $client = Client::factory()->for(Intake::factory(), 'originalIntake')->create();

    $html = view('pdf.client-consent', [
        'intake' => $client->originalIntake,
        'childName' => 'Mia Santos',
        'parentName' => 'Ana Santos',
        'dateOfBirth' => null,
        'fundingSource' => 'Private Pay',
        'isFscd' => false,
        'fscdNumber' => null,
        'caseWorkerName' => null,
        'caseWorkerEmail' => null,
        'legalName' => 'Creative Abilities Therapy Services Ltd.',
        'director' => 'Director',
    ])->render();

    expect($html)->toContain('src="data:image/png;base64,')
        ->and($html)->not->toContain(public_path('CatsLogo'));
});
