<?php

namespace Modules\Ecommerce\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\CourierProvider;

/**
 * Re-encrypts courier provider API credentials that the "encrypted" cast can
 * no longer read.
 *
 * courier_providers.api_key / api_secret use the "encrypted" cast. If a value
 * was stored as plaintext (saved before the cast existed, a direct DB insert,
 * or a DB import), every read throws DecryptException("The payload is invalid.")
 * — which blocks Send to Courier and the dashboard courier balance.
 *
 * This command reads the raw column, and for any value that does NOT already
 * decrypt cleanly with the current APP_KEY, it encrypts the raw value in place
 * so the cast can read it again. Idempotent: already-encrypted values are left
 * untouched, so it is safe to run repeatedly on a new site after seeding/import.
 *
 * NOTE: this recovers PLAINTEXT credentials. It cannot recover values that were
 * validly encrypted under a DIFFERENT APP_KEY (that plaintext is unrecoverable
 * — re-enter the key in Settings). Such values are reported and skipped.
 */
class FixCourierCredentials extends Command
{
    protected $signature = 'courier:fix-credentials {--dry-run : Report what would change without writing}';

    protected $description = 'Re-encrypt courier provider API credentials that the encrypted cast can no longer decrypt (e.g. stored as plaintext).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fields = ['api_key', 'api_secret'];

        $providers = CourierProvider::query()
            ->where(function ($q) use ($fields) {
                foreach ($fields as $f) {
                    $q->orWhereNotNull($f);
                }
            })
            ->get();

        if ($providers->isEmpty()) {
            $this->info('No courier providers with stored credentials found.');
            return self::SUCCESS;
        }

        $fixed = 0;
        $ok = 0;
        $unrecoverable = 0;

        foreach ($providers as $provider) {
            foreach ($fields as $field) {
                $raw = (string) $provider->getRawOriginal($field);
                if ($raw === '') {
                    continue;
                }

                if ($this->decryptsCleanly($raw)) {
                    $ok++;
                    continue;
                }

                // The raw value can't be decrypted. If it looks like a Laravel
                // encrypted payload, it was encrypted under a different APP_KEY
                // and the plaintext is unrecoverable — warn and skip. Otherwise
                // treat it as plaintext and encrypt it in place.
                if ($this->looksEncrypted($raw)) {
                    $this->warn("{$provider->slug}.{$field}: encrypted with a different APP_KEY — cannot recover. Re-enter it in Settings → Courier Providers.");
                    $unrecoverable++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("would fix: {$provider->slug}.{$field} (plaintext → encrypted)");
                } else {
                    DB::table($provider->getTable())
                        ->where('id', $provider->id)
                        ->update([$field => Crypt::encryptString($raw)]);
                    $this->line("fixed: {$provider->slug}.{$field}");
                }
                $fixed++;
            }
        }

        $this->newLine();
        $verb = $dryRun ? 'would be re-encrypted' : 're-encrypted';
        $this->info("Done. {$fixed} {$verb}, {$ok} already valid, {$unrecoverable} unrecoverable.");

        if ($dryRun && $fixed > 0) {
            $this->comment('Run again without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    /**
     * Whether the raw stored value decrypts with the current APP_KEY.
     */
    private function decryptsCleanly(string $raw): bool
    {
        try {
            Crypt::decryptString($raw);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }

    /**
     * Whether the raw value is shaped like a Laravel encrypted payload
     * (base64 of a JSON object with iv/value/mac). Used to distinguish
     * "plaintext" from "encrypted under a different key".
     */
    private function looksEncrypted(string $raw): bool
    {
        $decoded = base64_decode($raw, true);
        if ($decoded === false) {
            return false;
        }
        $json = json_decode($decoded, true);

        return is_array($json) && isset($json['iv'], $json['value'], $json['mac']);
    }
}
