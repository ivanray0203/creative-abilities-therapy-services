<?php

use App\Mail\StaffInviteMail;
use App\Mail\WelcomeClientAccountMail;
use Illuminate\Mail\Mailables\Attachment;

dataset('account mailables', [
    'staff invite' => [fn () => new StaffInviteMail('Tsedal', 'tsedal@example.com', 'secret1234')],
    'client welcome' => [fn () => new WelcomeClientAccountMail('Asmaa', 'asmaa@example.com', 'secret1234')],
]);

test('account emails attach the walkthrough video', function (Closure $mailable) {
    $video = tempnam(sys_get_temp_dir(), 'walkthrough').'.mp4';
    file_put_contents($video, 'not really a video');
    config(['cats.account_walkthrough_video' => $video]);

    $mail = $mailable();

    $mail->assertHasAttachment(
        Attachment::fromPath($video)->as('Demo-Video-Watch-Before-Logging-In.mp4')->withMime('video/mp4'),
    );

    expect($mail->render())->toContain('Before logging in, please watch the attached demo video');
})->with('account mailables');

test('account emails go out without the video when the file is missing', function (Closure $mailable) {
    config(['cats.account_walkthrough_video' => '/definitely/not/here.mp4']);

    expect($mailable()->attachments())->toBe([]);
})->with('account mailables');

test('the bundled walkthrough video exists at the configured path', function () {
    expect(config('cats.account_walkthrough_video'))->toBeFile();
});
