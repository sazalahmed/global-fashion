<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Services\SteadfastApiService;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;

/**
 * Re-applies development configuration after a production database import.
 *
 * An imported database brings credentials encrypted with the production
 * APP_KEY. A development key cannot decrypt them, so integrations report
 * themselves unconfigured, and local-only settings are overwritten by
 * whatever production held.
 *
 * courier:fix-credentials does not cover this: it re-encrypts values stored
 * as plaintext, and explicitly skips values encrypted under another key as
 * unrecoverable. Recovery needs the credentials supplied again, which is what
 * this command does.
 */
class RestoreLocalConfig extends Command
{
    protected $signature = 'dev:restore-local-config
        {--steadfast-key= : Steadfast API key (defaults to config localdev.steadfast.api_key)}
        {--steadfast-secret= : Steadfast API secret (defaults to config localdev.steadfast.api_secret)}
        {--business-start-date= : Business start date, YYYY-MM-DD (defaults to config localdev.business_start_date)}
        {--dry-run : Report what would change without writing}
        {--force : Run even when the environment is production}';

    protected $description = 'Re-apply local development config (courier credentials, business start date) after importing a production database.';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to run in production — this overwrites live credentials. Pass --force if that is genuinely intended.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->comment('Dry run — nothing will be written.');
            $this->newLine();
        }

        $changed = $this->restoreSteadfast($dryRun);
        $changed += $this->restoreBusinessStartDate($dryRun);

        $this->newLine();

        if ($changed === 0) {
            $this->info('Nothing to do — everything already matches.');

            return self::SUCCESS;
        }

        $this->info($dryRun ? "{$changed} item(s) would be updated." : "{$changed} item(s) updated.");

        if ($dryRun) {
            $this->comment('Run again without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    private function restoreSteadfast(bool $dryRun): int
    {
        $key = $this->option('steadfast-key') ?: config('localdev.steadfast.api_key');
        $secret = $this->option('steadfast-secret') ?: config('localdev.steadfast.api_secret');

        if (blank($key) || blank($secret)) {
            $this->line('Steadfast: skipped (no credentials supplied — pass --steadfast-key and --steadfast-secret, or set DEV_STEADFAST_API_KEY and DEV_STEADFAST_SECRET_KEY).');

            return 0;
        }

        $provider = CourierProvider::where('slug', 'steadfast')->first();

        if (! $provider) {
            $this->warn('Steadfast: no courier_providers row — run the courier seeder first.');

            return 0;
        }

        if (app(SteadfastApiService::class)->isConfigured()
            && $provider->api_key === $key
            && $provider->api_secret === $secret) {
            $this->line('Steadfast: already configured with these credentials.');

            return 0;
        }

        if ($dryRun) {
            $this->line('Steadfast: would re-encrypt the API key and secret with this environment\'s APP_KEY.');

            return 1;
        }

        // Clear credentials the current key cannot read before assigning: the
        // dirty check decrypts the stored value to compare, which throws.
        $provider->forgetUnreadableCredentials();
        $provider->api_key = $key;
        $provider->api_secret = $secret;
        $provider->is_active = true;
        $provider->save();

        app(SteadfastApiService::class)->forgetPaymentsCache();

        $configured = app(SteadfastApiService::class)->isConfigured();
        $this->line('Steadfast: credentials restored — ' . ($configured ? 'reports configured.' : 'STILL reports unconfigured, check the values.'));

        return 1;
    }

    private function restoreBusinessStartDate(bool $dryRun): int
    {
        $date = $this->option('business-start-date') ?: config('localdev.business_start_date');

        if (blank($date)) {
            $this->line('Business start date: skipped (none supplied).');

            return 0;
        }

        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            $this->warn("Business start date: '{$date}' is not a date I can read — skipped.");

            return 0;
        }

        if ((string) Setting::get('business', 'business_start_date') === $date) {
            $this->line("Business start date: already {$date}.");

            return 0;
        }

        if ($dryRun) {
            $this->line("Business start date: would set to {$date}.");

            return 1;
        }

        // Through the service so the settlement cache, which filters on this
        // date, is cleared with it.
        app(SettingService::class)->updateBusinessProfile(['business_start_date' => $date]);
        Setting::flushCache();

        $this->line("Business start date: set to {$date}.");

        return 1;
    }
}
