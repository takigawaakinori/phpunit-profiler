<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests\Integration;

use PHPUnit\Framework\TestCase;

class ExtensionIntegrationTest extends TestCase
{
    public function test_extension_outputs_profiler_results(): void
    {
        [$exitCode, $fullOutput] = $this->runFixturePhpunit('phpunit.xml');

        $this->assertSame(0, $exitCode, "PHPUnit failed:\n" . $fullOutput);
        $this->assertStringContainsString('Top 20 Slowest Tests', $fullOutput);
        $this->assertStringContainsString('SampleTest::test_slow', $fullOutput);
        $this->assertStringContainsString('SampleTest::test_fast', $fullOutput);
    }

    public function test_extension_falls_back_to_default_when_top_count_is_invalid(): void
    {
        [$exitCode, $fullOutput] = $this->runFixturePhpunit('phpunit-invalid-top-count.xml');

        $this->assertSame(0, $exitCode, "PHPUnit failed:\n" . $fullOutput);
        $this->assertStringContainsString('Top 20 Slowest Tests', $fullOutput);
        $this->assertStringNotContainsString('Top -1 Slowest Tests', $fullOutput);
    }

    public function test_extension_ignores_non_numeric_slow_threshold(): void
    {
        [$exitCode, $fullOutput] = $this->runFixturePhpunit('phpunit-invalid-slow-threshold.xml');

        $this->assertSame(0, $exitCode, "PHPUnit failed:\n" . $fullOutput);
        $this->assertStringNotContainsString('Tests slower than', $fullOutput);
    }

    /** @return array{int, string} */
    private function runFixturePhpunit(string $fixtureConfigFile): array
    {
        $phpunit = realpath(__DIR__ . '/../../vendor/bin/phpunit');
        $config = realpath(__DIR__ . '/Fixtures/' . $fixtureConfigFile);

        $this->assertNotFalse($phpunit, 'vendor/bin/phpunit not found. Run composer install first.');
        $this->assertNotFalse($config, "Fixture {$fixtureConfigFile} not found.");

        $command = sprintf('%s --configuration %s 2>&1', escapeshellarg($phpunit), escapeshellarg($config));

        exec($command, $output, $exitCode);

        return [$exitCode, implode("\n", $output)];
    }
}
