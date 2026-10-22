<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

#[Signature('app:backup-database')]
#[Description('Create a private MySQL database backup and remove expired backups')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $connection = DB::connection()->getConfig();

        if (($connection['driver'] ?? null) !== 'mysql') {
            $this->error('Backup otomatis saat ini hanya mendukung database MySQL.');

            return self::FAILURE;
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');

        $backupName = 'ssh-helpdesk-'.now()->format('Ymd-His');
        $databaseDumpPath = 'backups/.'.$backupName.'.sql.tmp';
        $archiveTemporaryPath = 'backups/.'.$backupName.'.zip.tmp';
        $backupPath = 'backups/'.$backupName.'.zip';
        $absoluteBackupPath = $disk->path($databaseDumpPath);
        $credentialsPath = 'backups/.mysql-client-'.Str::uuid().'.cnf';
        $credentialOptions = [
            'user' => (string) $connection['username'],
            'password' => (string) ($connection['password'] ?? ''),
            'default-character-set' => (string) $connection['charset'],
        ];

        if (filled($connection['unix_socket'] ?? null)) {
            $credentialOptions['socket'] = (string) $connection['unix_socket'];
        } else {
            $credentialOptions['host'] = (string) $connection['host'];
            $credentialOptions['port'] = (string) $connection['port'];
        }

        $credentialContents = "[client]\n";
        foreach ($credentialOptions as $key => $value) {
            $credentialContents .= $key.'='.$this->quoteOptionValue($value)."\n";
        }

        if (! $disk->put($credentialsPath, $credentialContents)) {
            $this->error('File konfigurasi sementara untuk backup gagal dibuat.');

            return self::FAILURE;
        }

        try {
            $process = new Process([
                (string) config('database.backup.mysql_dump_binary'),
                '--defaults-extra-file='.$disk->path($credentialsPath),
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--result-file='.$absoluteBackupPath,
                (string) $connection['database'],
            ], base_path(), null, null, 3600);
            $process->run();
        } catch (Throwable $exception) {
            $disk->delete($databaseDumpPath);
            report($exception);
            $this->error('Backup gagal dijalankan. Periksa mysqldump dan konfigurasi database.');

            return self::FAILURE;
        } finally {
            $disk->delete($credentialsPath);
        }

        if (! $process->isSuccessful() || ! $disk->exists($databaseDumpPath) || $disk->size($databaseDumpPath) === 0) {
            $disk->delete($databaseDumpPath);
            report('Database backup process failed with exit code '.$process->getExitCode().'.');
            $this->error('Backup database gagal. Periksa log aplikasi untuk detailnya.');

            return self::FAILURE;
        }

        try {
            $archiveCreated = $this->createArchive($databaseDumpPath, $archiveTemporaryPath);
            $archiveMoved = $archiveCreated && $disk->move($archiveTemporaryPath, $backupPath);
        } catch (Throwable $exception) {
            report($exception);
            $archiveMoved = false;
        } finally {
            $disk->delete([$databaseDumpPath, $archiveTemporaryPath]);
        }

        if (! $archiveMoved || ! $disk->exists($backupPath) || $disk->size($backupPath) === 0) {
            $disk->delete($backupPath);
            $this->error('Arsip backup gagal dibuat. Pastikan ekstensi ZIP PHP aktif dan penyimpanan tersedia.');

            return self::FAILURE;
        }

        $this->pruneExpiredBackups();
        $this->info('Backup database dan lampiran tersimpan: storage/app/private/'.$backupPath);

        return self::SUCCESS;
    }

    private function pruneExpiredBackups(): void
    {
        $disk = Storage::disk('local');
        $retentionDays = max(1, (int) config('database.backup.retention_days', 14));
        $expiresBefore = now()->subDays($retentionDays)->getTimestamp();

        foreach ($disk->files('backups') as $path) {
            if (str_starts_with(basename($path), 'ssh-helpdesk-') && str_ends_with($path, '.zip') && $disk->lastModified($path) < $expiresBefore) {
                $disk->delete($path);
            }
        }
    }

    private function createArchive(string $databaseDumpPath, string $archivePath): bool
    {
        $disk = Storage::disk('local');
        $archive = new \ZipArchive;

        if ($archive->open($disk->path($archivePath), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $filesAdded = $archive->addFile($disk->path($databaseDumpPath), 'database.sql');

        foreach ($disk->allFiles('ticket-attachments') as $attachmentPath) {
            $archiveRelativePath = 'attachments/'.substr($attachmentPath, strlen('ticket-attachments/'));
            if (! $archive->addFile($disk->path($attachmentPath), $archiveRelativePath)) {
                $filesAdded = false;
            }
        }

        return $archive->close() && $filesAdded;
    }

    private function quoteOptionValue(string $value): string
    {
        return '"'.str_replace(
            ['\\', '"', "\n", "\r"],
            ['\\\\', '\\"', '\\n', '\\r'],
            $value,
        ).'"';
    }
}
