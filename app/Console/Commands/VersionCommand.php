<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('version
    {action : show | bump:patch | bump:minor | bump:major | release}
    {--message= : Descrição da release (obrigatório para release)}')]
#[Description('Gerencia o versionamento semântico do sistema')]
class VersionCommand extends Command
{
    public function __construct(private readonly VersionService $versionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'show' => $this->show(),
            'bump:patch' => $this->bumpVersion('patch'),
            'bump:minor' => $this->bumpVersion('minor'),
            'bump:major' => $this->bumpVersion('major'),
            'release' => $this->release(),
            default => $this->invalidAction(),
        };
    }

    private function show(): int
    {
        $info = $this->versionService->info();

        $this->line('');
        $this->line("  <fg=cyan;options=bold>Loterias</> <fg=yellow>v{$info['version']}</>");
        $this->line("  Build: <fg=gray>{$info['git_hash']}</> ({$info['git_date']})");
        $this->line("  Env: <fg=green>{$info['environment']}</>");
        $this->line("  PHP {$info['php']} | Laravel {$info['laravel']}");
        $this->line('');

        return self::SUCCESS;
    }

    private function bumpVersion(string $type): int
    {
        $current = $this->versionService->current();
        $next = $this->versionService->bump($type);

        if (! $this->confirm("Bump <fg=yellow>{$current}</> → <fg=green>{$next}</> ({$type})?")) {
            $this->line('Operação cancelada.');

            return self::SUCCESS;
        }

        $this->versionService->write($next);
        $this->info("Versão atualizada: {$next}");
        $this->line("<fg=gray>Execute: git add VERSION && git commit -m \"chore: bump version to {$next}\"</>");

        return self::SUCCESS;
    }

    private function release(): int
    {
        $message = $this->option('message');

        if (! $message) {
            $message = $this->ask('Descrição da release (resumo das mudanças)');
        }

        if (! $message) {
            $this->error('Mensagem obrigatória para criar uma release.');

            return self::FAILURE;
        }

        $current = $this->versionService->current();

        $type = $this->choice(
            "Tipo de bump a partir de v{$current}",
            ['patch', 'minor', 'major'],
            0
        );

        $next = $this->versionService->bump($type);
        $date = now()->format('Y-m-d');

        $this->line('');
        $this->line("  <fg=cyan>Release:</> v{$current} → <fg=green>v{$next}</>");
        $this->line("  <fg=cyan>Data:</>    {$date}");
        $this->line("  <fg=cyan>Msg:</>     {$message}");
        $this->line('');

        if (! $this->confirm('Confirmar release?')) {
            $this->line('Operação cancelada.');

            return self::SUCCESS;
        }

        $this->versionService->write($next);
        $this->prependChangelog($next, $date, $message);

        $this->info("Release v{$next} preparada.");
        $this->line('');
        $this->line('<fg=yellow>Próximos passos:</>');
        $this->line('  git add VERSION CHANGELOG.md');
        $this->line("  git commit -m \"release: v{$next} — {$message}\"");
        $this->line("  git tag v{$next}");
        $this->line('  git push origin main --tags');

        return self::SUCCESS;
    }

    private function prependChangelog(string $version, string $date, string $message): void
    {
        $changelogPath = base_path('CHANGELOG.md');
        $entry = "\n## [{$version}] — {$date}\n\n{$message}\n\n---\n";

        if (! file_exists($changelogPath)) {
            file_put_contents($changelogPath, "# Changelog\n{$entry}");

            return;
        }

        $existing = file_get_contents($changelogPath);

        // Insere após a linha do primeiro "---" (separador após o cabeçalho)
        $existing = preg_replace('/^(---\s*\n)/m', "$1{$entry}", $existing, 1);

        file_put_contents($changelogPath, $existing);
    }

    private function invalidAction(): int
    {
        $this->error("Ação inválida: {$this->argument('action')}");
        $this->line('');
        $this->line('Ações disponíveis:');
        $this->line('  <fg=yellow>show</>        Exibe versão atual e informações do build');
        $this->line('  <fg=yellow>bump:patch</>  1.0.0 → 1.0.1 (bugfix, melhoria menor)');
        $this->line('  <fg=yellow>bump:minor</>  1.0.0 → 1.1.0 (nova tela, feature significativa)');
        $this->line('  <fg=yellow>bump:major</>  1.0.0 → 2.0.0 (quebra de compatibilidade)');
        $this->line('  <fg=yellow>release</>     Bump + atualiza CHANGELOG + instruções de tag');
        $this->line('');

        return self::FAILURE;
    }
}
