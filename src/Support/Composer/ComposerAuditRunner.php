<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Support\Composer;

use kayedspace\Doctor\Domain\Scan\SourceFile;

class ComposerAuditRunner
{
    /**
     * @param  string|array<int, string>  $composerCommand
     */
    public function __construct(
        private readonly string $projectRoot,
        private readonly string|array $composerCommand = 'composer',
        private readonly int $timeoutSeconds = 30,
    ) {}

    /**
     * @param  array<int, SourceFile>  $sourceFiles
     */
    public function run(array $sourceFiles = []): ComposerAuditContext
    {
        $errors = [];
        $composerJson = $this->readJsonFile('composer.json', $errors);
        $composerLock = [];

        if (! is_file($this->projectRoot.'/composer.lock')) {
            $errors[] = 'composer.lock is missing';
        } else {
            $composerLock = $this->readJsonFile('composer.lock', $errors);
        }

        $validateOutput = $this->runJsonCommand('validate', ['validate', '--format=json', '--no-interaction'], $errors);
        $auditOutput = [];
        $outdatedOutput = [];

        if ($composerLock !== []) {
            $auditOutput = $this->runJsonCommand('audit', ['audit', '--format=json', '--no-interaction'], $errors);
            $outdatedOutput = $this->runJsonCommand('outdated', ['outdated', '--direct', '--format=json', '--no-interaction'], $errors);
        }

        return new ComposerAuditContext(
            projectRoot: $this->projectRoot,
            composerJson: $composerJson,
            composerLock: $composerLock,
            auditOutput: $auditOutput,
            outdatedOutput: $outdatedOutput,
            validateOutput: $validateOutput,
            sourceFiles: $sourceFiles,
            errors: array_values(array_unique($errors)),
        );
    }

    /**
     * @param  array<int, string>  $errors
     * @return array<string, mixed>
     */
    private function readJsonFile(string $file, array &$errors): array
    {
        $path = $this->projectRoot.'/'.$file;
        if (! is_file($path)) {
            $errors[] = "{$file} is missing";

            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            $errors[] = "{$file} contains invalid JSON";

            return [];
        }

        return $decoded;
    }

    /**
     * @param  array<int, string>  $arguments
     * @param  array<int, string>  $errors
     * @return array<string, mixed>
     */
    private function runJsonCommand(string $name, array $arguments, array &$errors): array
    {
        $result = $this->runCommand($arguments);

        if ($result['timedOut']) {
            $errors[] = "Composer {$name} timed out";

            return [];
        }

        if ($result['unavailable']) {
            $errors[] = 'Composer is not available';

            return [];
        }

        if ($result['exitCode'] !== 0) {
            $errors[] = "Composer {$name} failed".($result['stderr'] !== '' ? ': '.$result['stderr'] : '');

            return [];
        }

        $decoded = json_decode($result['stdout'], true);
        if (! is_array($decoded)) {
            $errors[] = "Composer {$name} returned invalid JSON";

            return [];
        }

        return $decoded;
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array{stdout: string, stderr: string, exitCode: int, timedOut: bool, unavailable: bool}
     */
    private function runCommand(array $arguments): array
    {
        $command = array_merge(
            is_array($this->composerCommand) ? $this->composerCommand : [$this->composerCommand],
            $arguments
        );
        $pipes = [];
        $process = @proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $this->projectRoot
        );

        if (! is_resource($process)) {
            return ['stdout' => '', 'stderr' => '', 'exitCode' => 127, 'timedOut' => false, 'unavailable' => true];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = microtime(true);

        while (true) {
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);
            $status = proc_get_status($process);

            if (! $status['running']) {
                break;
            }

            if ((microtime(true) - $start) >= $this->timeoutSeconds) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                return ['stdout' => $stdout, 'stderr' => trim($stderr), 'exitCode' => 124, 'timedOut' => true, 'unavailable' => false];
            }

            usleep(10000);
        }

        $stdout .= (string) stream_get_contents($pipes[1]);
        $stderr .= (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        $stderr = trim($stderr);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
            'timedOut' => false,
            'unavailable' => $exitCode === 127 || str_contains(strtolower($stderr), 'not found'),
        ];
    }
}
