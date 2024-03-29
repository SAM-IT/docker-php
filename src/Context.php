<?php
declare(strict_types=1);

namespace SamIT\Docker;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Context
 * This class creates a temporary directory where it will incrementally create a docker build context.
 * There is no caching of commands, each command added will be immediately written to the docker file.
 * @package SamIT\Docker
 */
class Context
{
    private string $directory;
    private Filesystem $filesystem;

    public function __construct(?string $temp = null)
    {
        $this->filesystem = new Filesystem();
        $dir = $temp ?? sys_get_temp_dir();
        // Random file name:
        do {
            $name = "$dir/context_" . bin2hex(random_bytes(20));
        } while (file_exists("$dir/$name"));
        $this->filesystem->mkdir($name, 0777);
        $this->directory = $name;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function __destruct()
    {
        $this->filesystem->remove($this->directory);
    }

    public function command(string $command): void
    {
        $this->filesystem->appendToFile("{$this->directory}/Dockerfile", "$command\n");
    }

    public function add(string $path, string $content): void
    {
        $filename = 'file_' . hash('sha256', $content);
        $this->filesystem->dumpFile("{$this->directory}/$filename", $content);
        $this->command("ADD $filename $path");
    }

    public function copyFromLayer(string $path, string $sourceLayer, string $source): void
    {
        $this->command("COPY --from={$sourceLayer} {$source} {$path}");
    }

    public function addFile(string $path, string $source): void
    {
        $realPath = realpath($source);
        if ($realPath === false) {
            throw new \InvalidArgumentException("Failed to resolve real path for $source, does it exist?");
        }
        $name = 'disk_'. hash('sha256', $realPath);
        if (is_dir($source)) {
            $this->filesystem->mirror($source, "{$this->directory}/$name");
        } else {
            $this->filesystem->copy($source, "{$this->directory}/$name");
        }

        $this->command("ADD $name $path");
    }

    public function run(string $command): void
    {
        $this->command("RUN $command");
    }

    public function from(string $image): void
    {
        $this->command("FROM $image");
    }

    public function addUrl(string $path, string $url): void
    {
        $this->command("ADD $url $path");
    }

    public function volume(string $path): void
    {
        $this->command("VOLUME $path");
    }

    /**
     * @param non-empty-list<string> $entrypoint
     * @return void
     */
    public function entrypoint(array $entrypoint): void
    {
        $this->command("ENTRYPOINT " . json_encode($entrypoint, JSON_UNESCAPED_SLASHES));
    }

    public function env(string $name, string|int $value): void
    {
        $this->command("ENV $name $value");
    }
}
