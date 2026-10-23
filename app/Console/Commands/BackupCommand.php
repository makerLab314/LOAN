<?php

namespace App\Console\Commands;

use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Sichert Datenbank (mariadb-dump bzw. SQLite-Kopie) und hochgeladene Dateien.
 */
class BackupCommand extends Command
{
    protected $signature = 'loan:backup {--force : Auch ausführen, wenn Backups deaktiviert sind}';

    protected $description = 'Erstellt ein Backup von Datenbank und Uploads und löscht alte Backups';

    public function handle(Settings $settings): int
    {
        if (! $settings->get('backups.enabled') && ! $this->option('force')) {
            $this->info('Backups sind in den Einstellungen deaktiviert.');

            return self::SUCCESS;
        }

        $dir = rtrim((string) config('loan.backup_path'), '/');
        if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) {
            return $this->recordFailure("Backup-Ordner {$dir} kann nicht angelegt werden.", $settings);
        }

        $stamp = now()->format('Y-m-d_His');

        try {
            $this->dumpDatabase($dir.'/loan-db-'.$stamp);
            $this->archiveUploads($dir.'/loan-files-'.$stamp.'.tar.gz');
            $this->prune($dir, (int) $settings->get('backups.keep_days'));
        } catch (Throwable $e) {
            Log::error('Backup fehlgeschlagen', ['exception' => $e]);

            return $this->recordFailure($e->getMessage(), $settings);
        }

        $settings->set(['backups.last_run' => now()->toIso8601String(), 'backups.last_status' => 'ok']);
        $this->info('Backup erstellt: '.$dir);

        return self::SUCCESS;
    }

    private function dumpDatabase(string $base): void
    {
        $connection = config('database.default');
        $cfg = config('database.connections.'.$connection);

        if ($cfg['driver'] === 'sqlite') {
            if (! copy($cfg['database'], $base.'.sqlite')) {
                throw new \RuntimeException('SQLite-Datei konnte nicht kopiert werden.');
            }

            return;
        }

        $binary = trim((string) Process::run('command -v mariadb-dump || command -v mysqldump')->output());
        if ($binary === '') {
            throw new \RuntimeException('mariadb-dump/mysqldump nicht gefunden.');
        }

        $file = $base.'.sql.gz';
        $result = Process::env(['MYSQL_PWD' => (string) $cfg['password']])
            ->timeout(600)
            ->run(['bash', '-c', sprintf(
                'umask 077; set -o pipefail; %s --single-transaction --quick --routines --skip-lock-tables -h %s -P %s -u %s %s | gzip -9 > %s',
                escapeshellarg($binary),
                escapeshellarg((string) $cfg['host']),
                escapeshellarg((string) $cfg['port']),
                escapeshellarg((string) $cfg['username']),
                escapeshellarg((string) $cfg['database']),
                escapeshellarg($file),
            )]);

        if (! $result->successful()) {
            @unlink($file);
            throw new \RuntimeException('Datenbank-Dump fehlgeschlagen: '.trim($result->errorOutput()));
        }
    }

    private function archiveUploads(string $file): void
    {
        $source = storage_path('app/public');
        if (! is_dir($source)) {
            return;
        }

        $result = Process::timeout(600)->run(sprintf(
            'umask 077; tar -czf %s -C %s .',
            escapeshellarg($file),
            escapeshellarg($source),
        ));

        if (! $result->successful()) {
            throw new \RuntimeException('Archiv der Uploads fehlgeschlagen: '.trim($result->errorOutput()));
        }
    }

    private function prune(string $dir, int $keepDays): void
    {
        $limit = now()->subDays(max(1, $keepDays))->getTimestamp();
        foreach (glob($dir.'/loan-*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $limit) {
                @unlink($file);
            }
        }
    }

    private function recordFailure(string $message, Settings $settings): int
    {
        $settings->set(['backups.last_run' => now()->toIso8601String(), 'backups.last_status' => mb_substr($message, 0, 250)]);
        $this->error($message);

        return self::FAILURE;
    }
}
