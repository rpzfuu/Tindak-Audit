<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class AppInstall extends Command
{
    protected $signature = 'app:install {--force : Skip production confirmation} {--no-seed : Skip database seeders}';

    protected $description = 'Bootstrap project: env, key, schemas, migrate, seed, and storage link';

    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            if (! $this->confirm('Run installer in production?')) {
                return self::FAILURE;
            }
        }

        try {
            $this->newLine();
            $this->line('Installing TindakAudit...');

            $this->step('Checking required PHP extensions', fn () => $this->ensureExtensions());
            $this->step('Verifying .env', fn () => $this->ensureEnv());
            $this->step('Verifying APP_KEY', fn () => $this->ensureAppKey());
            $this->step('Verifying DB connections', fn () => $this->ensureDbConnections());
            $this->step('Bootstrapping superapps schema', fn () => $this->bootstrapSuperapps());
            $this->step('Running migrations', fn () => $this->callArtisan('migrate', ['--force' => true]));

            if (! $this->option('no-seed')) {
                $this->step('Running seeders', fn () => $this->callArtisan('db:seed', ['--force' => true]));
            }

            $this->step('Linking storage', fn () => $this->ensureStorageLink());
            $this->warnIfFrontendBuildIsMissing();
            $this->printDemoCredentials();

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function step(string $label, Closure $callback): void
    {
        $this->line("-> {$label}");

        $callback();

        $this->info('   OK');
    }

    private function ensureExtensions(): void
    {
        $missing = collect(['pdo_pgsql', 'mbstring', 'openssl'])
            ->reject(fn (string $extension) => extension_loaded($extension))
            ->values();

        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Missing PHP extension(s): '.$missing->implode(', '));
        }
    }

    private function ensureEnv(): void
    {
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            return;
        }

        $examplePath = base_path('.env.example');

        if (! File::exists($examplePath)) {
            throw new RuntimeException('.env.example not found.');
        }

        File::copy($examplePath, $envPath);
    }

    private function ensureAppKey(): void
    {
        $envPath = base_path('.env');
        $contents = File::exists($envPath) ? File::get($envPath) : '';

        if (preg_match('/^APP_KEY=.+$/m', $contents) === 1) {
            return;
        }

        $this->callArtisan('key:generate', ['--force' => true]);
    }

    private function ensureDbConnections(): void
    {
        foreach (['pgsql', 'superapps'] as $connection) {
            try {
                DB::connection($connection)->getPdo();
                DB::connection($connection)->select('select 1');
            } catch (Throwable $exception) {
                $database = config("database.connections.{$connection}.database");
                $host = config("database.connections.{$connection}.host");
                $port = config("database.connections.{$connection}.port");

                throw new RuntimeException(
                    "Cannot connect to {$connection} database ({$database}) at {$host}:{$port}. ".
                    'Create the database and verify credentials in .env. '.
                    $exception->getMessage(),
                );
            }
        }
    }

    private function bootstrapSuperapps(): void
    {
        if ($this->missingSuperappsTables() === []) {
            $this->line('   superapps schema already bootstrapped, skipping.');

            return;
        }

        $sqlPath = database_path('sql/superapps_bootstrap.sql');

        if (! File::exists($sqlPath)) {
            throw new RuntimeException('Missing database/sql/superapps_bootstrap.sql.');
        }

        DB::connection('superapps')->unprepared(File::get($sqlPath));

        $missing = $this->missingSuperappsTables();

        if ($missing !== []) {
            throw new RuntimeException('superapps bootstrap incomplete. Missing table(s): '.implode(', ', $missing));
        }
    }

    /**
     * @return array<int, string>
     */
    private function missingSuperappsTables(): array
    {
        $required = [
            'hris.unit_usaha',
            'hris.bagian',
            'hris.sub_bagian',
            'hris.karyawan',
            'hris.holiday',
            'public.users',
            'public.user_access',
        ];

        $tables = DB::connection('superapps')->select(
            "select table_schema, table_name
             from information_schema.tables
             where table_type = 'BASE TABLE'
               and (
                    (table_schema = 'hris' and table_name in ('unit_usaha', 'bagian', 'sub_bagian', 'karyawan', 'holiday'))
                 or (table_schema = 'public' and table_name in ('users', 'user_access'))
               )",
        );

        $existing = collect($tables)
            ->map(fn (object $table) => $table->table_schema.'.'.$table->table_name)
            ->all();

        return array_values(array_diff($required, $existing));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function callArtisan(string $command, array $parameters = []): void
    {
        $exitCode = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());

        if ($output !== '') {
            $this->line($output);
        }

        if ($exitCode !== self::SUCCESS) {
            throw new RuntimeException("Artisan command failed: {$command}");
        }
    }

    private function warnIfFrontendBuildIsMissing(): void
    {
        if (File::exists(public_path('build/manifest.json'))) {
            return;
        }

        $this->newLine();
        $this->warn('Frontend build manifest not found. Run: npm install && npm run build');
    }

    private function ensureStorageLink(): void
    {
        if (File::exists(public_path('storage'))) {
            $this->line('   public/storage already exists, skipping.');

            return;
        }

        $this->callArtisan('storage:link');
    }

    private function printDemoCredentials(): void
    {
        $this->newLine();
        $this->info('Installation complete.');

        if ($this->option('no-seed')) {
            $this->line('Seeder skipped. Run php artisan db:seed before using demo accounts.');

            return;
        }

        $this->line('Demo login:');
        $this->line('  SPI  : 19990001 / password');
        $this->line('  Unit : 19990003 / password');
        $this->line('Open: http://127.0.0.1:8000/login');
    }
}
