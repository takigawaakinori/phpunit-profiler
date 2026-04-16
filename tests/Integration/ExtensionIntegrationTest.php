<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests\Integration;

use PHPUnit\Framework\TestCase;

class ExtensionIntegrationTest extends TestCase
{
    public function test_extension_outputs_profiler_results(): void
    {
        $phpunit = realpath(__DIR__ . '/../../vendor/bin/phpunit');
        $config = realpath(__DIR__ . '/Fixtures/phpunit.xml');

        $this->assertNotFalse($phpunit, 'vendor/bin/phpunit not found. Run composer install first.');
        $this->assertNotFalse($config, 'Fixture phpunit.xml not found.');

        $command = sprintf('%s --configuration %s 2>&1', escapeshellarg($phpunit), escapeshellarg($config));

        exec($command, $output, $exitCode);

        $fullOutput = implode("\n", $output);

        $this->assertSame(0, $exitCode, "PHPUnit failed:\n" . $fullOutput);
        $this->assertStringContainsString('Top 20 Slowest Tests', $fullOutput);
        $this->assertStringContainsString('SampleTest::test_slow', $fullOutput);
        $this->assertStringContainsString('SampleTest::test_fast', $fullOutput);
    }
}
