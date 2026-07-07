<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use kayedspace\Doctor\Domain\DoctorRequest;

use function Laravel\Prompts\confirm;

class InstallSkillCommand extends Command
{
    protected $signature = 'doctor:install-skill {--client= : AI client name}';

    protected $description = 'Install the packaged Laravel Doctor AI workflow skill into a supported client';

    public function handle(Filesystem $files): int
    {
        $clients = Config::get('doctor.ai.skill.clients', []);
        if (! is_array($clients)) {
            $clients = [];
        }

        $client = (string) $this->option('client');
        if ($client === '' || ! isset($clients[$client]) || ! is_string($clients[$client])) {
            $this->error($client === '' ? 'Missing --client option.' : "Unsupported client: {$client}");
            $this->line('Supported clients: '.implode(', ', array_keys($clients)));

            return 1;
        }

        $relativeDestination = $clients[$client];
        if ($this->isUnsafeDestination($relativeDestination)) {
            $this->error('Skill destination must be a project-relative path without traversal.');

            return 1;
        }

        $projectRoot = (new DoctorRequest)->getProjectRoot();
        $destination = $projectRoot.'/'.ltrim(str_replace('\\', '/', $relativeDestination), '/');
        $this->line('Destination: '.$destination);

        $directory = dirname($destination);
        if (! $files->isDirectory($directory)) {
            $this->error('Destination directory does not exist. Create it explicitly before installing the skill.');

            return 1;
        }

        $template = __DIR__.'/../../resources/skills/doctor.md';
        if (! $files->isFile($template)) {
            $this->error('Packaged Doctor skill template is missing.');

            return 1;
        }

        if ($files->exists($destination) && ! confirm(label: 'Overwrite existing skill file?', default: false)) {
            $this->info('Installation cancelled; existing file unchanged.');

            return 0;
        }

        $contents = $files->get($template);
        $files->put($destination, $contents);

        $this->info('Laravel Doctor skill installed.');

        // Gemini/Qwen concatenate a single context file rather than auto-discovering
        // a skills/rules directory, so the installed fragment needs an explicit import.
        if (in_array($client, ['gemini', 'qwen'], true)) {
            $contextFile = $client === 'gemini' ? 'GEMINI.md' : 'QWEN.md';
            $this->line("Add \"@{$relativeDestination}\" to your {$contextFile} so {$client} loads it.");
        }

        return 0;
    }

    private function isUnsafeDestination(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) === 1
            || str_contains(str_replace('\\', '/', $path), '..');
    }
}
