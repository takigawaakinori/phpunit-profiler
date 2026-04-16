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
        $outputter->printTopN($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('Top 20 Slowest Tests', $output);
        $this->assertStringContainsString('1.234s', $output);
        $this->assertStringContainsString('App\\Tests\\FooTest::test_slow', $output);
    }

    public function test_print_top20_no_output_when_empty(): void
    {
        $results = new TestDurationResultCollection();
        $outputter = new TestDurationOutputter();

        ob_start();
        $outputter->printTopN($results);
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
        $outputter->printTopN($results);
        $output = ob_get_clean();

        // Should contain rank 20 but not rank 21
        $this->assertStringContainsString('20.', $output);
        $this->assertStringNotContainsString('21.', $output);
    }

    public function test_print_topN_with_custom_count(): void
    {
        $items = [];
        for ($i = 0; $i < 10; $i++) {
            $items[] = new TestDurationResult("Test::test_{$i}", (float) $i);
        }
        $results = new TestDurationResultCollection(...$items);

        $outputter = new TestDurationOutputter(5);

        ob_start();
        $outputter->printTopN($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('Top 5 Slowest Tests:', $output);
        $this->assertStringContainsString(' 5.', $output);
        $this->assertStringNotContainsString(' 6.', $output);
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

    public function test_print_hides_topN_when_disabled(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Test::test_a', 1.0),
        );

        $outputter = new TestDurationOutputter(showTopN: false);

        ob_start();
        $outputter->print($results);
        $output = ob_get_clean();

        $this->assertStringNotContainsString('Slowest Tests', $output);
    }

    public function test_print_shows_topN_by_default(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Test::test_a', 1.0),
        );

        $outputter = new TestDurationOutputter();

        ob_start();
        $outputter->print($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('Top 20 Slowest Tests', $output);
    }

    public function test_print_shows_pareto_when_enabled(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Test::test_a', 1.0),
            new TestDurationResult('Test::test_b', 0.5),
        );

        $outputter = new TestDurationOutputter(showPareto: true);

        ob_start();
        $outputter->print($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('Top 20 Slowest Tests', $output);
        $this->assertStringContainsString('Pareto:', $output);
    }

    public function test_print_hides_pareto_by_default(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Test::test_a', 1.0),
        );

        $outputter = new TestDurationOutputter();

        ob_start();
        $outputter->print($results);
        $output = ob_get_clean();

        $this->assertStringNotContainsString('Pareto:', $output);
    }

    public function test_print_slow_threshold_filters_by_duration(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Test::test_slow', 2.0),
            new TestDurationResult('Test::test_medium', 0.5),
            new TestDurationResult('Test::test_fast', 0.1),
        );

        $outputter = new TestDurationOutputter(slowThreshold: 1.0);

        ob_start();
        $outputter->printSlowThreshold($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('Tests slower than 1.000s:', $output);
        $this->assertStringContainsString('Test::test_slow', $output);
        $this->assertStringNotContainsString('Test::test_medium', $output);
        $this->assertStringNotContainsString('Test::test_fast', $output);
    }

    public function test_print_slow_threshold_shows_none_when_all_fast(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Test::test_fast', 0.01),
        );

        $outputter = new TestDurationOutputter(slowThreshold: 1.0);

        ob_start();
        $outputter->printSlowThreshold($results);
        $output = ob_get_clean();

        $this->assertStringContainsString('(none)', $output);
    }
}
