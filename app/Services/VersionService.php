<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VersionService
{
    private string $versionFile;

    public function __construct()
    {
        $this->versionFile = base_path('VERSION');
    }

    public function current(): string
    {
        if (! file_exists($this->versionFile)) {
            return '0.0.0';
        }

        return trim(file_get_contents($this->versionFile));
    }

    public function write(string $version): void
    {
        file_put_contents($this->versionFile, $version."\n");
    }

    public function bump(string $type): string
    {
        [$major, $minor, $patch] = array_map('intval', explode('.', $this->current()));

        match ($type) {
            'major' => [$major, $minor, $patch] = [$major + 1, 0, 0],
            'minor' => [$major, $minor, $patch] = [$major, $minor + 1, 0],
            'patch' => [$major, $minor, $patch] = [$major, $minor, $patch + 1],
            default => throw new \InvalidArgumentException("Tipo inválido: {$type}. Use major, minor ou patch."),
        };

        return "{$major}.{$minor}.{$patch}";
    }

    public function gitHash(): string
    {
        $hash = trim((string) shell_exec('git rev-parse --short HEAD 2>/dev/null'));

        return $hash ?: 'unknown';
    }

    public function gitDate(): string
    {
        $date = trim((string) shell_exec('git log -1 --format=%ci 2>/dev/null'));

        return $date ?: 'unknown';
    }

    public function gitTag(): string
    {
        $tag = trim((string) shell_exec('git describe --tags --exact-match HEAD 2>/dev/null'));

        return $tag ?: '';
    }

    public function dbVersion(): string
    {
        try {
            $driver = DB::connection()->getDriverName();
            $raw = DB::selectOne('SELECT VERSION() as v');

            return $driver.': '.($raw->v ?? 'unknown');
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * @return array<string, string>
     */
    public function info(): array
    {
        return [
            'version' => $this->current(),
            'git_hash' => $this->gitHash(),
            'git_date' => $this->gitDate(),
            'git_tag' => $this->gitTag(),
            'environment' => app()->environment(),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'db' => $this->dbVersion(),
        ];
    }
}
