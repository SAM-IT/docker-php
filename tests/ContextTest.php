<?php
declare(strict_types=1);

namespace tests;


use PHPUnit\Framework\TestCase;
use SamIT\Docker\Context;
use SamIT\Docker\Docker;

/**
 * @covers \SamIT\Docker\Context
 */
class ContextTest extends TestCase
{
    public function testEntrypoint()
    {
        $context = new Context();
        $context->from('alpine:edge');
        $context->entrypoint(['/bin/sh', '-c', 'echo -n "command with \"special characters: "']);


        $tag = bin2hex(random_bytes(16));
        exec("docker build {$context->getDirectory()} -t {$tag} 2>&1", $output, $result);
        $this->assertSame(0, $result, "Build output: " . print_r($output, true));
        $output = shell_exec("docker run --rm -t {$tag}");
        $this->assertSame('command with "special characters: ', $output);

    }
}
