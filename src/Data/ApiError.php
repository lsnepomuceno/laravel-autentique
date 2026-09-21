<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Illuminate\Support\Str;
use LSNepomuceno\LaravelAutentique\Enums\ErrorCode;

/**
 * One entry of a GraphQL response's `errors` array.
 */
final readonly class ApiError
{
    /**
     * @param  list<string|int>  $path  Where in the operation the error arose, `["createFolder"]`.
     * @param  list<Violation>  $violations  Empty unless the message is `validation`.
     */
    public function __construct(
        public string $message,
        public ?ErrorCode $code,
        public array $path,
        public array $violations,
    ) {}

    /**
     * @param  array<mixed>  $error  One entry of `errors`, as decoded.
     */
    public static function fromArray(array $error): self
    {
        $message = is_string($error['message'] ?? null) ? $error['message'] : 'Unknown error.';
        $path = is_array($error['path'] ?? null) ? $error['path'] : [];
        $extensions = is_array($error['extensions'] ?? null) ? $error['extensions'] : [];
        $validation = is_array($extensions['validation'] ?? null) ? $extensions['validation'] : [];

        $violations = [];

        foreach ($validation as $field => $codes) {
            foreach (is_array($codes) ? $codes : [$codes] as $code) {
                if (is_string($code)) {
                    $violations[] = Violation::parse((string) $field, $code);
                }
            }
        }

        return new self(
            message: $message,
            code: ErrorCode::tryFrom(Str::lower(Str::before($message, ':'))),
            path: array_values(array_filter($path, fn(mixed $segment): bool => is_string($segment) || is_int($segment))),
            violations: $violations,
        );
    }

    public function isValidation(): bool
    {
        return $this->message === 'validation';
    }

    public function isUnauthorized(): bool
    {
        return $this->code === ErrorCode::Unauthorized;
    }

    /**
     * Whether the error says the thing asked for does not exist, by code or by
     * the sentence Autentique writes for it, `Folder not found`.
     */
    public function isNotFound(): bool
    {
        return ($this->code?->meansNotFound() ?? false)
            || Str::endsWith(Str::lower(rtrim($this->message, '.')), 'not found');
    }
}
