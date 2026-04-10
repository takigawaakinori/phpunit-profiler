<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests\Integration\Fixtures;

use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    public function test_fast(): void
    {
        $this->assertTrue(true);
    }

    public function test_slow(): void
    {
        usleep(50_000); // 50ms
        $this->assertTrue(true);
    }
}
