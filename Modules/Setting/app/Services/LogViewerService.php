<?php

namespace Modules\Setting\Services;

class LogViewerService
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }

    /**
     * Get all log files sorted by last modified.
     */
    public function getLogFiles(): array
    {
        $files = glob($this->logPath . '/*.log');
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'filename'      => basename($file),
                'size'          => filesize($file),
                'size_human'    => $this->humanFileSize(filesize($file)),
                'last_modified' => filemtime($file),
                'last_modified_human' => date('d M Y, h:i A', filemtime($file)),
            ];
        }

        usort($result, fn ($a, $b) => $b['last_modified'] <=> $a['last_modified']);

        return $result;
    }

    /**
     * Parse a log file and return structured entries.
     */
    public function parseLogFile(string $filename, array $filters = [], int $limit = 50): array
    {
        $filepath = $this->resolvePath($filename);
        if (!$filepath) {
            return [];
        }

        $content = file_get_contents($filepath);
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s(\w+)\.(\w+):\s(.*)$/m';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $entries = [];
        $totalMatches = count($matches);

        for ($i = $totalMatches - 1; $i >= 0; $i--) {
            $match = $matches[$i];
            $timestamp = $match[1][0];
            $env = $match[2][0];
            $level = strtoupper($match[3][0]);
            $message = $match[4][0];

            // Extract stack trace (content between this match and next)
            $startPos = $match[0][1] + strlen($match[0][0]);
            $endPos = ($i < $totalMatches - 1) ? $matches[$i + 1][0][1] : strlen($content);
            $stackTrace = trim(substr($content, $startPos, $endPos - $startPos));

            // Apply filters
            if (!empty($filters['level']) && $level !== strtoupper($filters['level'])) {
                continue;
            }
            if (!empty($filters['search']) && stripos($message . $stackTrace, $filters['search']) === false) {
                continue;
            }
            if (!empty($filters['date']) && !str_starts_with($timestamp, $filters['date'])) {
                continue;
            }

            $entries[] = [
                'timestamp'   => $timestamp,
                'environment' => $env,
                'level'       => $level,
                'message'     => $message,
                'stack_trace' => $stackTrace,
            ];

            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    /**
     * Get stats for a log file.
     */
    public function getLogStats(string $filename): array
    {
        $filepath = $this->resolvePath($filename);
        if (!$filepath) {
            return [];
        }

        $content = file_get_contents($filepath);
        $levels = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];
        $stats = [];

        foreach ($levels as $level) {
            $stats[strtolower($level)] = substr_count($content, ".{$level}:");
        }

        return $stats;
    }

    /**
     * Delete a log file.
     */
    public function deleteLogFile(string $filename): bool
    {
        $filepath = $this->resolvePath($filename);
        return $filepath ? unlink($filepath) : false;
    }

    /**
     * Clear (truncate) a log file.
     */
    public function clearLogFile(string $filename): bool
    {
        $filepath = $this->resolvePath($filename);
        return $filepath ? (file_put_contents($filepath, '') !== false) : false;
    }

    /**
     * Get full path to a log file (with security validation).
     */
    public function getFilePath(string $filename): ?string
    {
        return $this->resolvePath($filename);
    }

    /**
     * Resolve and validate file path to prevent traversal.
     */
    private function resolvePath(string $filename): ?string
    {
        // Security: only allow alphanumeric, hyphens, underscores, dots
        if (!preg_match('/^[a-zA-Z0-9\-_.]+\.log$/', $filename)) {
            return null;
        }

        $filepath = $this->logPath . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filepath) || !is_file($filepath)) {
            return null;
        }

        // Ensure the resolved path is inside the logs directory
        $realPath = realpath($filepath);
        $realLogPath = realpath($this->logPath);
        if (!str_starts_with($realPath, $realLogPath)) {
            return null;
        }

        return $filepath;
    }

    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
