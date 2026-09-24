<?php

use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

it('accepts step 2 submission with an NCR address', function (): void {
    $sessionData = [
        'registration.step1' => [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'password_hash' => 'dummy-hash',
        ],
        'registration.otp.account.verified' => true,
    ];

    $response = $this->withSession($sessionData)->post(route('company-registration.storeStep2'), [
        'company_name' => 'Metro Express Inc',
        'company_email' => 'contact@metroexpress.ph',
        'company_phone' => '09181234567',
        'company_address' => 'Unit 10A High Street South, Fort Bonifacio, City of Taguig, NCR',
        'business_type' => 'corporate',
        'registration_number' => 'CS202012345',
        'authorized_representative_name' => 'Maria Santos',
        'authorized_representative_position' => 'President',
        'authorized_representative_contact' => '09191234567',
    ]);

    $response->assertRedirect()->assertSessionDoesntHaveErrors();

    expect(session('registration.step2.company_address'))
        ->toBe('Unit 10A High Street South, Fort Bonifacio, City of Taguig, NCR')
        ->and(session('registration.step2.company_name'))
        ->toBe('Metro Express Inc');
});

it('accepts step 2 submission with a provincial address', function (): void {
    $sessionData = [
        'registration.step1' => [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'password_hash' => 'dummy-hash',
        ],
        'registration.otp.account.verified' => true,
    ];

    $response = $this->withSession($sessionData)->post(route('company-registration.storeStep2'), [
        'company_name' => 'Cavite Transport Services',
        'company_email' => 'admin@cavitetransport.ph',
        'company_phone' => '09189876543',
        'company_address' => 'Block 5 Lot 10 Aguinaldo Highway, Bucandala, City of Imus, Cavite, CALABARZON',
        'business_type' => 'corporate',
        'registration_number' => 'CS202054321',
        'authorized_representative_name' => 'Pedro Penduko',
        'authorized_representative_position' => 'Operations Manager',
        'authorized_representative_contact' => '09198765432',
    ]);

    $response->assertRedirect()->assertSessionDoesntHaveErrors();

    expect(session('registration.step2.company_address'))
        ->toBe('Block 5 Lot 10 Aguinaldo Highway, Bucandala, City of Imus, Cavite, CALABARZON');
});

it('requires company_address on step 2', function (): void {
    $sessionData = [
        'registration.step1' => [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'password_hash' => 'dummy-hash',
        ],
    ];

    $response = $this->withSession($sessionData)->from(route('company-registration.show'))->post(route('company-registration.storeStep2'), [
        'company_name' => 'Metro Express Inc',
        'company_email' => 'contact@metroexpress.ph',
        'company_phone' => '09181234567',
        'company_address' => '',
        'business_type' => 'corporate',
        'registration_number' => 'CS202012345',
        'authorized_representative_name' => 'Maria Santos',
        'authorized_representative_position' => 'President',
        'authorized_representative_contact' => '09191234567',
    ]);

    $response->assertRedirect(route('company-registration.show'))
        ->assertSessionHasErrors(['company_address']);
});
