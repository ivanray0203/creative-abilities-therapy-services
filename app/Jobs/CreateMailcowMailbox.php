<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ported from cats-backend/cats/utils/mailcow.py::create_mailbox_async,
 * triggered from ApplicationHiringService::hire() on the same transition
 * (Application.hired = true) as the reference. Laravel's queue retry/backoff
 * replaces the reference's manual sleep loop; "already exists" and
 * non-JSON responses short-circuit without retrying, same as the reference.
 */
class CreateMailcowMailbox implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $teamMemberId,
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $password,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [2, 4, 8];
    }

    public function handle(): void
    {
        $localPart = $this->localPart();
        $domain = (string) config('services.mailcow.default_domain');
        $apiUrl = rtrim((string) config('services.mailcow.api_url'), '/');

        $response = Http::withHeaders(['X-API-Key' => (string) config('services.mailcow.api_key')])
            ->timeout(10)
            ->post("{$apiUrl}/add/mailbox", [
                'local_part' => $localPart,
                'domain' => $domain,
                'name' => trim("{$this->firstName} {$this->lastName}"),
                'password' => $this->password,
                'password2' => $this->password,
                'quota' => 3072,
                'active' => 1,
                'tls_enforce_in' => 1,
                'tls_enforce_out' => 1,
                'force_pw_update' => 0,
            ]);

        $this->assertProvisioned($response, "{$localPart}@{$domain}");
    }

    private function assertProvisioned(Response $response, string $mailbox): void
    {
        if (! $response->successful()) {
            throw new RuntimeException("Mailcow API returned HTTP {$response->status()} provisioning {$mailbox}.");
        }

        $body = $response->json();

        if (! is_array($body)) {
            Log::error("Mailcow returned a non-JSON response provisioning {$mailbox}; not retrying.");

            return;
        }

        $entry = $body[0] ?? $body;
        $type = $entry['type'] ?? null;
        $message = is_array($entry['msg'] ?? null)
            ? implode(' ', $entry['msg'])
            : (string) ($entry['msg'] ?? '');

        if ($type === 'success') {
            return;
        }

        if (str_contains(Str::lower($message), 'already exists')) {
            Log::info("Mailcow mailbox {$mailbox} already exists; treating as provisioned.");

            return;
        }

        throw new RuntimeException("Mailcow API error provisioning {$mailbox}: {$message}");
    }

    private function localPart(): string
    {
        $first = Str::lower(trim($this->firstName));
        $last = Str::lower(trim($this->lastName));

        return match (true) {
            $first === '' => $last,
            $last === '' => $first,
            default => "{$first}.{$last}",
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Mailcow mailbox provisioning failed after all retries.', [
            'team_member_id' => $this->teamMemberId,
            'error' => $exception->getMessage(),
        ]);
    }
}
