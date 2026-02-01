<?php
namespace Core;

use Core\Models\FreegateModel;

class FreegateScanner
{
    public static function run(string $type): array
    {
        $scanId = FreegateModel::createScan($type);
        $findings = [];

        try {
            $result = match ($type) {
                'quick' => self::scanFiles(['php', 'ini']),
                'full' => self::scanFiles(['php', 'ini', 'sql', 'xml']),
                'integrity' => self::scanIntegrity(),
                'risk' => self::scanRisk(),
                default => self::scanFiles(['php']),
            };
            foreach ($result['findings'] as $finding) {
                FreegateModel::addFinding($scanId, $finding['severity'], $finding['message'], $finding['path']);
            }

            $summary = $result['summary'];
            FreegateModel::finishScan($scanId, 'complete', $summary);
            $titleKey = match ($type) {
                'quick' => 'FFCMS.FREEGATE_TIMELINE_SCAN_QUICK',
                'full' => 'FFCMS.FREEGATE_TIMELINE_SCAN_FULL',
                'integrity' => 'FFCMS.FREEGATE_TIMELINE_SCAN_INTEGRITY',
                'risk' => 'FFCMS.FREEGATE_TIMELINE_SCAN_RISK',
                default => 'FFCMS.FREEGATE_TIMELINE_SCAN',
            };
            FreegateModel::addTimeline([
                'event_type' => 'scan',
                'title' => $titleKey,
                'details' => $summary,
                'severity' => $result['findings'] ? 'medium' : 'low',
                'correlation_id' => null,
                'actor_user_id' => null,
            ]);
            return [
                'scan_id' => $scanId,
                'status' => 'complete',
                'summary' => $summary,
                'findings' => $result['findings'],
            ];
        } catch (\Throwable $e) {
            FreegateModel::finishScan($scanId, 'failed', 'Scan failed.');
            $titleKey = match ($type) {
                'quick' => 'FFCMS.FREEGATE_TIMELINE_SCAN_QUICK',
                'full' => 'FFCMS.FREEGATE_TIMELINE_SCAN_FULL',
                'integrity' => 'FFCMS.FREEGATE_TIMELINE_SCAN_INTEGRITY',
                'risk' => 'FFCMS.FREEGATE_TIMELINE_SCAN_RISK',
                default => 'FFCMS.FREEGATE_TIMELINE_SCAN',
            };
            FreegateModel::addTimeline([
                'event_type' => 'scan',
                'title' => $titleKey,
                'details' => 'Scan failed.',
                'severity' => 'high',
                'correlation_id' => null,
                'actor_user_id' => null,
            ]);
            return [
                'scan_id' => $scanId,
                'status' => 'failed',
                'summary' => 'Scan failed.',
                'findings' => [],
            ];
        }
    }

    private static function scanFiles(array $extension): array
    {
        $files = self::collectFiles($extension);
        $findings = [];
        foreach ($files as $file) {
            if (!is_readable($file)) {
                $findings[] = self::finding('medium', 'File not readable', $file);
            }
        }

        $summary = $findings ? 'Issues detected in file access.' : 'No file access issues detected.';
        return ['summary' => $summary, 'findings' => $findings];
    }

    private static function scanIntegrity(): array
    {
        $files = self::collectFiles(['php', 'ini', 'sql', 'xml']);
        $baseline = FreegateModel::integrityHashes();
        $findings = [];

        foreach ($files as $file) {
            $relative = self::relativePath($file);
            $hash = hash_file('sha256', $file) ?: '';
            if ($hash === '') {
                $findings[] = self::finding('medium', 'Hash failed', $relative);
                continue;
            }

            if (!isset($baseline[$relative])) {
                FreegateModel::saveIntegrity($relative, $hash);
                $findings[] = self::finding('low', 'Baseline created', $relative);
                continue;
            }

            if ($baseline[$relative] !== $hash) {
                $findings[] = self::finding('high', 'Integrity mismatch', $relative);
            }
        }

        $summary = $findings ? 'Integrity scan completed with findings.' : 'Integrity scan clean.';
        return ['summary' => $summary, 'findings' => $findings];
    }

    private static function scanRisk(): array
    {
        $findings = [];
        $paths = [
            dirname(__DIR__) . '/core',
            dirname(__DIR__) . '/admin',
        ];
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            $perm = fileperms($path);
            if (($perm & 0x0002) !== 0) {
                $findings[] = self::finding('high', 'World-writable directory', self::relativePath($path));
            }
        }

        $summary = $findings ? 'Risk audit detected issues.' : 'Risk audit clean.';
        return ['summary' => $summary, 'findings' => $findings];
    }

    private static function collectFiles(array $extension): array
    {
        $root = dirname(__DIR__);
        $targets = [$root . '/core', $root . '/admin', $root . '/install', $root . '/index.php'];
        $files = [];
        foreach ($targets as $target) {
            if (is_file($target)) {
                $files[] = $target;
                continue;
            }
            if (!is_dir($target)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($target));
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (!in_array($ext, $extension, true)) {
                    continue;
                }
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private static function finding(string $severity, string $message, string $path): array
    {
        return [
            'severity' => $severity,
            'message' => $message,
            'path' => $path,
        ];
    }

    private static function relativePath(string $path): string
    {
        $root = dirname(__DIR__);
        $normalized = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', $root);
        if (str_starts_with($normalized, $root)) {
            $relative = ltrim(substr($normalized, strlen($root)), '/');
            return $relative === '' ? '.' : $relative;
        }
        return $normalized;
    }
}
