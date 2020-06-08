<?php
declare(strict_types=1);

namespace tests;


use PHPUnit\Framework\TestCase;
use SamIT\Docker\Context;
use SamIT\Docker\Docker;

/**
 * @covers \SamIT\Docker\Context
 * @uses \SamIT\Docker\Docker
 */
class ContextTest extends TestCase
{
    public function testEntrypoint()
    {
        $context = new Context();
        $context->from('alpine:edge');
        $context->entrypoint(['/bin/sh', '-c', 'echo -n "command with \"special characters: "']);

        $docker = new Docker();
        $tag = bin2hex(random_bytes(16));
        ob_start();
        $docker->build($context, $tag);
        ob_get_clean();

        ob_start();
        $result = $docker->run($tag);
        $output = ob_get_clean();
        $this->assertSame(0, $result);
        $this->assertSame('command with "special characters: ', $output);

    }
}