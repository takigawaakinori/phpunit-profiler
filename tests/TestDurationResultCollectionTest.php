<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests;

use PHPUnit\Framework\TestCase;
use TakigawaAkinori\PhpunitProfiler\TestDurationResult;
use TakigawaAkinori\PhpunitProfiler\TestDurationResultCollection;

class TestDurationResultCollectionTest extends TestCase
{
    public function test_top_returns_first_n_results(): void
    {
        $collection = new TestDurationResultCollection(
            new TestDurationResult('test_1', 5.0),
            new TestDurationResult('test_2', 3.0),
            new TestDurationResult('test_3', 1.0),
        );

        $top = $collection->top(2);

        $this->assertCount(2, $top);

        $items = iterator_to_array($top);
        $this->assertSame('test_1', $items[0]->testId);
        $this->assertSame('test_2', $items[1]->testId);
    }

    public function test_top_percentile(): void
    {
        $results = [];
        for ($i = 1; $i <= 10; $i++) {
            $results[] = new TestDurationResult("test_{$i}", (float) $i);
        }

        $collection = new TestDurationResultCollection(...$results);
        $top20 = $collection->topPercentile(20);

        // 10 * 20% = 2
        $this->assertCount(2, $top20);
    }

    public function test_total_duration(): void
    {
        $collection = new TestDurationResultCollection(
            new TestDurationResult('test_1', 1.5),
            new TestDurationResult('test_2', 2.5),
            new TestDurationResult('test_3', 3.0),
        );

        $this->assertEqualsWithDelta(7.0, $collection->totalDuration(), 0.001);
    }

    public function test_is_empty(): void
    {
        $empty = new TestDurationResultCollection();
        $this->assertTrue($empty->isEmpty());

        $nonEmpty = new TestDurationResultCollection(
            new TestDurationResult('test_1', 1.0),
        );
        $this->assertFalse($nonEmpty->isEmpty());
    }

    public function test_count(): void
    {
        $collection = new TestDurationResultCollection(
            new TestDurationResult('test_1', 1.0),
            new TestDurationResult('test_2', 2.0),
        );

        $this->assertCount(2, $collection);
    }
}
