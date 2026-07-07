<?php

declare(strict_types=1);

namespace kayedspace\Doctor\Tests\Support;

class GitFixture
{
    public readonly string $root;

    public function __construct()
    {
        $this->root = sys_get_temp_dir().'/doctor-git-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
        $this->run('git init -q');
        $this->run('git config user.email doctor@example.test');
        $this->run('git config user.name Doctor');
    }

    public function write(string $path, string $contents): void
    {
        $target = $this->root.'/'.$path;
        $dir = dirname($target);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($target, $contents);
    }

    public function commit(string $message = 'commit'): void
    {
        $this->run('git add .');
        $this->run('git commit -qm '.escapeshellarg($message));
    }

    public function run(string $command): string
    {
        $output = [];
        $code = 0;
        exec('cd '.escapeshellarg($this->root).' && '.$command.' 2>&1', $output, $code);

        if ($code !== 0) {
            throw new \RuntimeException(implode("\n", $output));
        }

        return implode("\n", $output);
    }
}
