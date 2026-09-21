<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Data\Input\{DocumentConfig,
    Expiration,
    Locale,
    NewDocument,
    Position,
    SecurityVerification,
    Signer};
use LSNepomuceno\LaravelAutentique\Enums\{Action,
    DateFormat,
    DeliveryMethod,
    Footer,
    Language,
    PositionElement,
    Reminder,
    SignatureAppearance,
    VerificationType};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * The input value objects: what they send, and what they refuse before a
 * request is spent.
 */

describe('Position', function () {
    it('writes coordinates as strings with one decimal, as Autentique takes them', function () {
        expect(Position::signature(5, 90.25, page: 2)->toArray())
            ->toBe(['x' => '5.0', 'y' => '90.3', 'z' => 2, 'element' => 'SIGNATURE']);
    });

    it('carries the element, the angle and the scale', function () {
        expect(new Position(10, 20, 1, PositionElement::Initials, angle: 90, scale: 1.5)->toArray())
            ->toBe(['x' => '10.0', 'y' => '20.0', 'z' => 1, 'element' => 'INITIALS', 'angle' => 90.0, 'scale' => 1.5]);
    });

    it('has a named constructor for every element', function () {
        expect(Position::name(1, 1)->element)->toBe(PositionElement::Name)
            ->and(Position::initials(1, 1)->element)->toBe(PositionElement::Initials)
            ->and(Position::date(1, 1)->element)->toBe(PositionElement::Date)
            ->and(Position::cpf(1, 1)->element)->toBe(PositionElement::Cpf);
    });

    it('refuses what is outside the page or the documented ranges', function (Closure $build, string $message) {
        expect($build)->toThrow(InvalidInput::class, $message);
    })->with([
        'x above 100' => [fn() => Position::signature(101, 10), 'x and y go from 0 to 100'],
        'y below 0' => [fn() => Position::signature(10, -1), 'x and y go from 0 to 100'],
        'page 0' => [fn() => Position::signature(10, 10, page: 0), 'Pages start at 1'],
        'angle 361' => [fn() => new Position(10, 10, angle: 361), 'from 0 to 360'],
        'scale 5' => [fn() => new Position(10, 10, scale: 5), 'from 0.5 to 4'],
    ]);
});

describe('SecurityVerification', function () {
    it('sends only what it carries', function () {
        expect(SecurityVerification::sms('+5554999999999')->toArray())->toBe(['type' => 'SMS', 'verify_phone' => '+5554999999999'])
            ->and(SecurityVerification::live()->withoutFallback()->toArray())->toBe(['type' => 'LIVE', 'fallback_behavior' => 'DISABLE_FALLBACK']);
    });

    it('has a named constructor for every type', function () {
        $types = array_map(fn(SecurityVerification $check): VerificationType => $check->type, [
            SecurityVerification::sms(),
            SecurityVerification::manual(),
            SecurityVerification::upload(),
            SecurityVerification::live(),
            SecurityVerification::pfFacial(),
            SecurityVerification::pfFacialMatch(),
            SecurityVerification::biometricAndTextExtraction(),
            SecurityVerification::livenessAndTextExtraction(),
        ]);

        expect($types)->toBe(VerificationType::cases());
    });
});

describe('Signer', function () {
    it('is reached by email, signing by default', function () {
        expect(Signer::email('ana@example.com')->toArray())->toBe(['email' => 'ana@example.com', 'action' => 'SIGN']);
    });

    it('is reached by WhatsApp or SMS', function () {
        expect(Signer::whatsapp('+5554999999999', Action::Approve)->toArray())
            ->toBe(['phone' => '+5554999999999', 'delivery_method' => 'DELIVERY_METHOD_WHATSAPP', 'action' => 'APPROVE'])
            ->and(Signer::sms('+5521999999999')->deliveryMethod)->toBe(DeliveryMethod::Sms);
    });

    it('receives a link by name, bound to an address when one is given', function () {
        expect(Signer::link('Ronaldo')->toArray())->toBe(['name' => 'Ronaldo', 'action' => 'SIGN'])
            ->and(Signer::link('João', phone: '+5554999999998')->toArray())
            ->toBe(['name' => 'João', 'phone' => '+5554999999998', 'delivery_method' => 'DELIVERY_METHOD_LINK', 'action' => 'SIGN']);
    });

    it('carries positions, checks, a CPF and a type, without changing the original', function () {
        $original = Signer::email('ana@example.com');

        $signer = $original
            ->withName('Ana')
            ->withAction(Action::SignAsWitness)
            ->withPosition(Position::signature(5, 90))
            ->withPosition(Position::date(55, 90))
            ->withVerification(SecurityVerification::sms())
            ->withVerification(SecurityVerification::manual())
            ->withCpf('12345678900')
            ->qualified();

        expect($signer->toArray())->toBe([
            'email' => 'ana@example.com',
            'name' => 'Ana',
            'action' => 'SIGN_AS_A_WITNESS',
            'positions' => [
                ['x' => '5.0', 'y' => '90.0', 'z' => 1, 'element' => 'SIGNATURE'],
                ['x' => '55.0', 'y' => '90.0', 'z' => 1, 'element' => 'DATE'],
            ],
            'security_verifications' => [['type' => 'SMS'], ['type' => 'MANUAL']],
            'configs' => ['cpf' => '12345678900'],
            'type' => 'QUALIFIED',
        ])->and($original->positions)->toBe([]);
    });

    it('refuses what Autentique would refuse', function (Closure $build, string $message) {
        expect($build)->toThrow(InvalidInput::class, $message);
    })->with([
        'no address' => [fn() => new Signer(Action::Sign), 'an email, a name or a phone'],
        'a local phone' => [fn() => Signer::whatsapp('54999999999'), 'international format'],
        'a phone with no way to reach it' => [fn() => new Signer(Action::Sign, phone: '+5554999999999'), 'needs a delivery method'],
        'two photo checks' => [
            fn() => Signer::email('a@example.com')->withVerification(SecurityVerification::manual())->withVerification(SecurityVerification::live()),
            'only one of the MANUAL, UPLOAD, LIVE and PF_FACIAL',
        ],
    ]);

    it('lets SMS stand beside a photo check', function () {
        $signer = Signer::email('a@example.com')
            ->withVerification(SecurityVerification::sms())
            ->withVerification(SecurityVerification::pfFacial());

        expect($signer->verifications)->toHaveCount(2);
    });
});

describe('Locale', function () {
    it('sends what it carries', function () {
        expect(new Locale('US', Language::EnglishUnitedStates, 'America/New_York', DateFormat::MonthDayYear)->toArray())
            ->toBe(['country' => 'US', 'language' => 'en-US', 'timezone' => 'America/New_York', 'date_format' => 'MM_DD_YYYY']);
    });

    it('knows a Brazilian document, which is the default', function () {
        expect(new Locale()->isBrazilian())->toBeTrue()
            ->and(new Locale('BR')->isBrazilian())->toBeTrue()
            ->and(new Locale('US')->isBrazilian())->toBeFalse();
    });

    it('refuses a country or timezone that does not exist', function (Closure $build) {
        expect($build)->toThrow(InvalidInput::class);
    })->with([
        'lower case country' => [fn() => new Locale('br')],
        'a country name' => [fn() => new Locale('Brazil')],
        'a made up timezone' => [fn() => new Locale(timezone: 'America/Nowhere')],
    ]);
});

describe('Expiration', function () {
    it('writes the date the way Autentique reads it', function () {
        expect(new Expiration(CarbonImmutable::parse('2026-01-20'), 7)->toArray())
            ->toBe(['days_before' => 7, 'notify_at' => '20/01/2026']);
    });

    it('refuses a reminder on the day itself', function () {
        new Expiration(CarbonImmutable::parse('2026-01-20'), 0);
    })->throws(InvalidInput::class, 'at least 1 day');
});

describe('NewDocument', function () {
    it('sends only the name when nothing else is set', function () {
        expect(new NewDocument('Contract')->toArray())->toBe(['name' => 'Contract']);
    });

    it('sends every option in the shape Autentique takes', function () {
        $document = new NewDocument(
            name: 'Marketing contract',
            message: 'Please sign',
            reminder: Reminder::Weekly,
            refusable: true,
            stopOnRejected: true,
            footer: Footer::Bottom,
            newSignatureStyle: true,
            showAuditPage: false,
            emailTemplateId: 1234,
            deadline: CarbonImmutable::parse('2023-11-23 23:59:59.999', 'America/Sao_Paulo'),
            watchers: ['watcher@example.com'],
            cc: ['cc@example.com'],
            expiration: new Expiration(CarbonImmutable::parse('2026-01-20'), 7),
            configs: new DocumentConfig(notifyFinished: true, signatureAppearance: SignatureAppearance::Electronic),
            locale: new Locale('BR'),
        );

        expect($document->toArray())->toBe([
            'name' => 'Marketing contract',
            'message' => 'Please sign',
            'reminder' => 'WEEKLY',
            'footer' => 'BOTTOM',
            'refusable' => true,
            'stop_on_rejected' => true,
            'new_signature_style' => true,
            'show_audit_page' => false,
            'email_template_id' => 1234,
            'deadline_at' => '2023-11-24T02:59:59.999Z',
            'watchers' => ['watcher@example.com'],
            'cc' => [['email' => 'cc@example.com']],
            'expiration' => ['days_before' => 7, 'notify_at' => '20/01/2026'],
            'configs' => ['notification_finished' => true, 'signature_appearance' => 'ELETRONIC'],
            'locale' => ['country' => 'BR'],
        ]);
    });

    it('refuses the combinations Autentique refuses or overrides', function (Closure $build, string $message) {
        expect($build)->toThrow(InvalidInput::class, $message);
    })->with([
        'no name' => [fn() => new NewDocument('  '), 'needs a name'],
        'a reminder on a document that cannot be refused' => [
            fn() => new NewDocument('x', reminder: Reminder::Daily, refusable: false),
            'makes the document refusable',
        ],
        'stopping on a rejection nobody can make' => [fn() => new NewDocument('x', stopOnRejected: true), 'needs a refusable document'],
        'no audit page with the old style' => [fn() => new NewDocument('x', showAuditPage: false), 'new signature style'],
        'PDF/A with unlocked user data' => [
            fn() => new NewDocument('x', configs: new DocumentConfig(lockUserData: false, pdfa: true)),
            'PDF/A keeps user data locked',
        ],
    ]);

    it('accepts a reminder when refusable is left to Autentique', function () {
        expect(new NewDocument('x', reminder: Reminder::Daily)->toArray())->toBe(['name' => 'x', 'reminder' => 'DAILY']);
    });
});
