<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests;

use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Framework\TestCase;
use TakigawaAkinori\PhpunitProfiler\TestTimeCollector;

class TestTimeCollectorTest extends TestCase
{
    public function test_records_and_returns_results(): void
    {
        $collector = new TestTimeCollector();

        $collector->recordStart('App\\Tests\\FooTest::test_example', HRTime::fromSecondsAndNanoseconds(0, 0));
        $collector->recordEnd('App\\Tests\\FooTest::test_example', HRTime::fromSecondsAndNanoseconds(1, 500_000_000));

        $results = $collector->getResults();

        $this->assertCount(1, $results);
        $this->assertFalse($results->isEmpty());

        foreach ($results as $result) {
            $this->assertSame('App\\Tests\\FooTest::test_example', $result->testId);
            $this->assertEqualsWithDelta(1.5, $result->durationInSeconds, 0.001);
        }
    }

    public function test_empty_results_when_no_tests_recorded(): void
    {
        $collector = new TestTimeCollector();

        $results = $collector->getResults();

        $this->assertTrue($results->isEmpty());
        $this->assertCount(0, $results);
    }

    public function test_record_end_without_start_is_ignored(): void
    {
        $collector = new TestTimeCollector();

        $collector->recordEnd('App\\Tests\\FooTest::test_unknown', HRTime::fromSecondsAndNanoseconds(1, 0));

        $results = $collector->getResults();

        $this->assertTrue($results->isEmpty());
    }

    public function test_results_are_sorted_by_slowest_first(): void
    {
        $collector = new TestTimeCollector();

        // fast test: 0.1s
        $collector->recordStart('App\\Tests\\FooTest::test_fast', HRTime::fromSecondsAndNanoseconds(0, 0));
        $collector->recordEnd('App\\Tests\\FooTest::test_fast', HRTime::fromSecondsAndNanoseconds(0, 100_000_000));

        // slow test: 5.0s
        $collector->recordStart('App\\Tests\\FooTest::test_slow', HRTime::fromSecondsAndNanoseconds(1, 0));
        $collector->recordEnd('App\\Tests\\FooTest::test_slow', HRTime::fromSecondsAndNanoseconds(6, 0));

        $results = $collector->getResults();
        $items = iterator_to_array($results);

        $this->assertSame('App\\Tests\\FooTest::test_slow', $items[0]->testId);
        $this->assertSame('App\\Tests\\FooTest::test_fast', $items[1]->testId);
    }
}
