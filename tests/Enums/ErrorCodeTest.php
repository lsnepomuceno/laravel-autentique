<?php

declare(strict_types=1);

use LSNepomuceno\LaravelAutentique\Data\Violation;
use LSNepomuceno\LaravelAutentique\Enums\ErrorCode;

/**
 * The documented codes, and their text in both locales.
 */

it('has a message for every code in every shipped locale', function (string $locale) {
    app()->setLocale($locale);

    foreach (ErrorCode::cases() as $code) {
        expect($code->message())->not->toBe("autentique::errors.{$code->value}");
    }
})->with(['en', 'pt_BR']);

it('ships no message for a code the enum does not have', function (string $locale) {
    $messages = require packageRoot() . "/lang/{$locale}/errors.php";

    expect(array_keys(is_array($messages) ? $messages : []))
        ->toEqualCanonicalizing(array_map(fn(ErrorCode $code): string => $code->value, ErrorCode::cases()));
})->with(['en', 'pt_BR']);

it('follows the application locale', function () {
    app()->setLocale('pt_BR');

    expect(ErrorCode::DocumentNotFound->message())->toBe('Documento não encontrado.');
});

it('fills the parameter a code carries', function () {
    expect(Violation::parse('folder.name', 'must_be_at_least_characters:3')->message())
        ->toBe("Can't have less than 3 characters.");
});

it('keeps a code it does not know as it arrived', function () {
    $violation = Violation::parse('document.name', 'brand_new_rule:7');

    expect($violation->code)->toBeNull()
        ->and($violation->rawCode)->toBe('brand_new_rule')
        ->and($violation->parameter)->toBe('7')
        ->and($violation->message())->toBe('brand_new_rule');
});

it('knows which codes mean not found', function () {
    expect(ErrorCode::DocumentNotFound->meansNotFound())->toBeTrue()
        ->and(ErrorCode::FieldRequired->meansNotFound())->toBeFalse();
});
