<?php

namespace Modules\Security\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * List all backups from the storage directory.
     */
    public function list(): array
    {
        $files = glob($this->backupPath . '/*.sql.gz') ?: [];
        $backups = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'id'         => $filename,
                'filename'   => $filename,
                'size'       => filesize($file),
                'type'       => str_contains($filename, 'full') ? 'Full' : 'Database',
                'status'     => 'completed',
                'created_at' => date('d M Y, h:i A', filemtime($file)),
                'timestamp'  => filemtime($file),
            ];
        }

        // Sort by timestamp descending (newest first)
        usort($backups, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

        return $backups;
    }

    /**
     * Create a database backup using mysqldump.
     */
    public function create(string $type = 'database'): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $prefix = $type === 'full' ? 'full' : 'db';
        $filename = "backup_{$prefix}_{$timestamp}.sql.gz";
        $filepath = $this->backupPath . '/' . $filename;

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $result = Process::timeout(300)->run($command);

        if (!$result->successful()) {
            throw new \RuntimeException('Backup failed: ' . $result->errorOutput());
        }

        return $filename;
    }

    /**
     * Restore a database from a backup file.
     */
    public function restore(string $backupId): void
    {
        $filepath = $this->backupPath . '/' . $backupId;

        if (!file_exists($filepath)) {
            throw new \RuntimeException('Backup file not found.');
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'gunzip -c %s | mysql --host=%s --port=%s --user=%s --password=%s %s',
            escapeshellarg($filepath),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database)
        );

        $result = Process::timeout(600)->run($command);

        if (!$result->successful()) {
            throw new \RuntimeException('Restore failed: ' . $result->errorOutput());
        }
    }

    /**
     * Get backup stats.
     */
    public function getStats(): array
    {
        $files = glob($this->backupPath . '/*.sql.gz') ?: [];
        $totalSize = 0;
        $lastBackupDate = null;

        foreach ($files as $file) {
            $totalSize += filesize($file);
            $mtime = filemtime($file);
            if ($lastBackupDate === null || $mtime > $lastBackupDate) {
                $lastBackupDate = $mtime;
            }
        }

        return [
            'total_backups'    => count($files),
            'last_backup'      => $lastBackupDate ? date('d M Y', $lastBackupDate) : 'Never',
            'storage_used'     => $this->formatBytes($totalSize),
            'storage_used_raw' => $totalSize,
        ];
    }

    /**
     * Delete a backup file.
     */
    public function delete(string $backupId): void
    {
        $filepath = $this->backupPath . '/' . $backupId;

        if (!file_exists($filepath)) {
            throw new \RuntimeException('Backup file not found.');
        }

        unlink($filepath);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 0) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }
}
