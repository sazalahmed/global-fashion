<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid {--force : Overwrite existing keys without confirmation}';

    protected $description = 'Generate a new VAPID key pair for Web Push and write it to the .env file';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error('.env file not found at ' . $envPath);
            return self::FAILURE;
        }

        $env = file_get_contents($envPath);

        $hasExisting = preg_match('/^VAPID_PUBLIC_KEY=.+$/m', $env);
        if ($hasExisting && !$this->option('force')) {
            if (!$this->confirm('VAPID keys already exist in .env. Overwrite?', false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            $this->error('Failed to generate keys: ' . $e->getMessage());
            $this->line('On Windows, set OPENSSL_CONF to a valid openssl.cnf path before running this command.');
            return self::FAILURE;
        }

        $env = $this->setEnvKey($env, 'VAPID_PUBLIC_KEY', $keys['publicKey']);
        $env = $this->setEnvKey($env, 'VAPID_PRIVATE_KEY', $keys['privateKey']);

        if (!preg_match('/^VAPID_SUBJECT=.+$/m', $env)) {
            $env = $this->setEnvKey($env, 'VAPID_SUBJECT', 'mailto:admin@bizpos.test');
        }

        file_put_contents($envPath, $env);

        $this->info('VAPID keys generated and written to .env.');
        $this->line('Public key:  ' . $keys['publicKey']);
        $this->line('Private key: ' . str_repeat('*', 12) . substr($keys['privateKey'], -4));
        $this->newLine();
        $this->comment('Run `php artisan config:clear` to refresh cached config.');

        return self::SUCCESS;
    }

    private function setEnvKey(string $env, string $key, string $value): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $line    = $key . '=' . $value;

        if (preg_match($pattern, $env)) {
            return preg_replace($pattern, $line, $env);
        }

        return rtrim($env, "\n") . "\n" . $line . "\n";
    }
}
