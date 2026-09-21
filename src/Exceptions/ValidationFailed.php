<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

use LSNepomuceno\LaravelAutentique\Data\Violation;

/**
 * Autentique refused a value: an error whose message is `validation`, with the
 * codes per field in `extensions.validation`.
 */
final class ValidationFailed extends RequestFailed
{
    /**
     * Every violation, from every error.
     *
     * @return list<Violation>
     */
    public function violations(): array
    {
        $violations = [];

        foreach ($this->errors as $error) {
            $violations = [...$violations, ...$error->violations];
        }

        return $violations;
    }

    /**
     * The violations grouped by field, as the application's own validator
     * would group them, with each message in the application's locale.
     *
     * @return array<string, list<string>>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->violations() as $violation) {
            $messages[$violation->field][] = $violation->message();
        }

        return $messages;
    }
}
