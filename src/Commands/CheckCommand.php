<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use LSNepomuceno\LaravelAutentique\Contracts\Autentique;
use LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException;

/**
 * Answers, before anything is sent, whether this application can talk to
 * Autentique: is there a token, does the endpoint answer, does it accept the
 * token, whose account is it, and are documents sandbox by default.
 *
 * It costs one `me` query, which Autentique does not bill.
 */
final class CheckCommand extends Command
{
    /** @var string */
    protected $signature = 'autentique:check';

    /** @var string */
    protected $description = 'Check the Autentique token and endpoint, and show whose account it is';

    public function handle(Autentique $autentique, Repository $config): int
    {
        $this->components->twoColumnDetail('Endpoint', $this->text($config->get('autentique.url')));
        $this->components->twoColumnDetail(
            'Sandbox by default',
            $config->get('autentique.sandbox') === true ? '<fg=yellow>yes</>' : 'no',
        );

        try {
            $user = $autentique->account()->me();
        } catch (AutentiqueException $exception) {
            $this->components->twoColumnDetail('Token', '<fg=red>not usable</>');
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Token', '<fg=green>accepted</>');
        $this->components->twoColumnDetail('Account', trim(($user->name ?? '') . ' <' . ($user->email ?? '') . '>'));
        $this->components->twoColumnDetail('Organization', $user->organization->name ?? '');

        if ($user->subscription !== null) {
            $this->components->twoColumnDetail('Documents left', $this->text($user->subscription->documents));
            $this->components->twoColumnDetail('Verification credits', $this->text($user->subscription->credits));
        }

        $this->components->info('Autentique is reachable and accepts the token.');

        return self::SUCCESS;
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
