<?php
declare(strict_types=1);

namespace SamIT\Docker;

class Docker
{
    public function build(Context $context, string $tag): void
    {
        passthru("docker build -t {$tag} {$context->getDirectory()}", $result);
        if ($result !== 0) {
            throw new \RuntimeException("Build failed", $result);
        }
    }

    public function push(string $tag): void
    {
        passthru("docker push {$tag}", $result);
        if ($result !== 0) {
            throw new \RuntimeException("Push failed", $result);
        }
    }
}
