<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests;

use PHPUnit\Framework\TestCase;
use TakigawaAkinori\PhpunitProfiler\TestDurationOutputter;
use TakigawaAkinori\PhpunitProfiler\TestDurationResult;
use TakigawaAkinori\PhpunitProfiler\TestDurationResultCollection;

class TestDurationOutputterTest extends TestCase
{
    public function test_print_top20_shows_results(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('App\\Tests\\FooTest::test_slow', 1.234),
            new TestDurationResult('App\\Tests\\FooTest::test_fast', 0.001),
        );

        $outputter = new TestDurationOutputter();

        ob_start();
        $outputter->printTop20($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('Top 20 Slowest Tests:', $output);
        $this->assertStringContainsString('1.234s', $output);
        $this->assertStringContainsString('App\\Tests\\FooTest::test_slow', $output);
    }

    public function test_print_top20_no_output_when_empty(): void
    {
        $results = new TestDurationResultCollection();
        $outputter = new TestDurationOutputter();

        ob_start();
        $outputter->printTop20($results);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function test_print_top20_limits_to_20(): void
    {
        $items = [];
        for ($i = 0; $i < 25; $i++) {
            $items[] = new TestDurationResult("Test::test_{$i}", (float) $i);
        }
        $results = new TestDurationResultCollection(...$items);

        $outputter = new TestDurationOutputter();

        ob_start();
        $outputter->printTop20($results);
        $output = ob_get_clean();

        // Should contain rank 20 but not rank 21
        $this->assertStringContainsString('20.', $output);
        $this->assertStringNotContainsString('21.', $output);
    }

    public function test_print_pareto_shows_percentage(): void
    {
        $items = [];
        for ($i = 1; $i <= 10; $i++) {
            $items[] = new TestDurationResult("Test::test_{$i}", (float) $i);
        }
        $results = new TestDurationResultCollection(...$items);

        $outputter = new TestDurationOutputter();

        ob_start();
        $outputter->printPareto($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('Pareto:', $output);
        $this->assertStringContainsString('Top 20%', $output);
        $this->assertStringContainsString('2 / 10', $output);
    }
}
