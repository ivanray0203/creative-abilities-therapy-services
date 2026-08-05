<?php

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a visitor can submit the public contact form', function () {
    $payload = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '5874338780',
        'subject' => 'Question about services',
        'message' => 'Hello, I would like to know more about your services.',
    ];

    $response = $this->post('/contacts', $payload);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Contact::count())->toBe(1);

    $contact = Contact::first();
    expect($contact->contact)->toMatchArray($payload);
});

test('the contact form requires name, email, subject, and message', function () {
    $response = $this->post('/contacts', []);

    $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

    expect(Contact::count())->toBe(0);
});
