<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\GraphQL;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\{ConnectionException, Factory, PendingRequest, RequestException, Response};
use LSNepomuceno\LaravelAutentique\Contracts\{FileSource, GraphQLClient};
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, MissingToken, OAuthFailed, TransportFailed};
use Throwable;

/**
 * The one class that reaches Autentique.
 *
 * Built on Laravel's HTTP client, injected, for JSON and multipart alike, so
 * `Http::fake()` and `Http::preventStrayRequests()` in an application reach
 * every request the package makes (docs/spec/invariants.md, rule 1;
 * docs/decisions/0004-the-transport-is-laravels-http-client.md).
 *
 * Only HTTP 429 is retried: a timeout or a 5xx may have been processed, and a
 * mutation repeated after one can duplicate a billed document
 * (docs/decisions/0009-only-a-refused-request-is-retried.md).
 */
final readonly class Client implements GraphQLClient
{
    public function __construct(
        private Factory $http,
        private OperationLoader $loader,
        private ResponseParser $parser,
        private Repository $config,
        #[\SensitiveParameter]
        private ?string $token = null,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    #[\Override]
    public function send(Operation $operation, array $variables = [], ?FileSource $file = null): array
    {
        $payload = [
            'query' => $this->loader->load($operation),
            'variables' => $variables === [] ? new \stdClass() : $variables,
            'operationName' => $operation->operationName(),
        ];

        $url = $this->url($operation->endpoint());

        if ($file === null) {
            return $this->post(
                $operation->operationName(),
                fn(PendingRequest $request): Response => $request->asJson()->post($url, $payload),
            );
        }

        // The GraphQL multipart request specification: `operations` carries the
        // document with the file variable set to null, `map` says which part
        // fills it, and the file follows, in that order.
        $payload['variables'] = [...$variables, 'file' => null];

        return $this->post(
            $operation->operationName(),
            fn(PendingRequest $request): Response => $request
                ->attach('operations', json_encode($payload, JSON_THROW_ON_ERROR))
                ->attach('map', json_encode(['file' => ['variables.file']], JSON_THROW_ON_ERROR))
                ->attach('file', $file->contents(), $file->name())
                ->post($url),
        );
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    #[\Override]
    public function raw(string $document, array $variables = []): array
    {
        $url = $this->url(Endpoint::Standard);
        $payload = ['query' => $document, 'variables' => $variables === [] ? new \stdClass() : $variables];

        return $this->post(null, fn(PendingRequest $request): Response => $request->asJson()->post($url, $payload));
    }

    /**
     * @param  array<string, string>  $form
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    #[\Override]
    public function oauthToken(#[\SensitiveParameter] array $form): array
    {
        $url = rtrim($this->text('autentique.oauth.url'), '/') . '/token';

        try {
            $response = $this->http
                ->createPendingRequest()
                ->acceptJson()
                ->asForm()
                ->timeout($this->integer('autentique.timeout', 30))
                ->post($url, $form);
        } catch (ConnectionException $exception) {
            throw new TransportFailed('Could not reach Autentique\'s OAuth token endpoint.', 'oauth', previous: $exception);
        }

        $body = $this->decode($response);

        if ($response->successful() && is_array($body) && is_string($body['access_token'] ?? null)) {
            /** @var array<string, mixed> $body */
            return $body;
        }

        $error = is_array($body) && is_string($body['error'] ?? null) ? $body['error'] : null;
        $description = is_array($body) && is_string($body['error_description'] ?? null) ? $body['error_description'] : null;

        throw new OAuthFailed(
            'Autentique refused the token request' . ($error === null ? '' : ": {$error}") . ($description === null ? '.' : ", {$description}"),
            $error,
            $response->status(),
            $this->header($response, 'X-Attq-Request-Id'),
        );
    }

    #[\Override]
    public function withToken(#[\SensitiveParameter] string $token): self
    {
        return new self($this->http, $this->loader, $this->parser, $this->config, $token);
    }

    /**
     * @param  \Closure(PendingRequest): Response  $send
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    private function post(?string $operation, \Closure $send): array
    {
        try {
            $response = $send($this->request());
        } catch (ConnectionException $exception) {
            throw new TransportFailed(
                'Could not reach Autentique: the connection failed or timed out. The request may have been processed.',
                $operation,
                previous: $exception,
            );
        }

        $body = $this->decode($response);

        return $this->parser->parse(
            status: $response->status(),
            body: $body,
            operation: $operation,
            requestId: $this->header($response, 'X-Attq-Request-Id'),
            retryAfter: $this->header($response, 'Retry-After'),
        );
    }

    /**
     * @throws MissingToken
     */
    private function request(): PendingRequest
    {
        $token = $this->token ?? $this->config->get('autentique.token');

        if (! is_string($token) || $token === '') {
            throw MissingToken::make();
        }

        $times = $this->integer('autentique.retry.times', 2);
        $sleep = $this->integer('autentique.retry.sleep', 1000);

        return $this->http
            ->createPendingRequest()
            ->withToken($token)
            ->acceptJson()
            ->timeout($this->integer('autentique.timeout', 30))
            ->retry(
                // retry() counts attempts, the first one included.
                times: max(1, $times + 1),
                sleepMilliseconds: fn(int $attempt, mixed $exception): int => $this->wait($attempt, $sleep, $exception),
                when: fn(Throwable $exception): bool => $exception instanceof RequestException
                    && $exception->response->status() === 429,
                throw: false,
            );
    }

    /**
     * How long to wait before the next attempt: what Autentique asked for, or
     * the configured pause times the attempt number.
     */
    private function wait(int $attempt, int $sleep, mixed $exception): int
    {
        if ($exception instanceof RequestException) {
            $asked = $exception->response->header('Retry-After');

            if (is_numeric($asked)) {
                return (int) $asked * 1000;
            }
        }

        return $sleep * $attempt;
    }

    private function header(Response $response, string $name): ?string
    {
        $value = $response->header($name);

        return $value === '' ? null : $value;
    }

    private function decode(Response $response): mixed
    {
        try {
            return json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    private function url(Endpoint $endpoint): string
    {
        $url = $this->config->get("autentique.{$endpoint->value}");

        return is_string($url) ? $url : '';
    }

    private function text(string $key): string
    {
        $value = $this->config->get($key);

        return is_string($value) ? $value : '';
    }

    private function integer(string $key, int $default): int
    {
        $value = $this->config->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }
}
