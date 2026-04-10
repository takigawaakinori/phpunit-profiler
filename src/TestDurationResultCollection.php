<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, TestDurationResult>
 */
final class TestDurationResultCollection implements IteratorAggregate, Countable
{
    /** @var list<TestDurationResult> */
    private readonly array $results;

    public function __construct(TestDurationResult ...$results)
    {
        $this->results = array_values($results);
    }

    public function top(int $n): self
    {
        return new self(...array_slice($this->results, 0, $n));
    }

    public function topPercentile(int $percentile): self
    {
        $count = (int) ceil(count($this->results) * $percentile / 100);

        return $this->top($count);
    }

    public function totalDuration(): float
    {
        $total = 0.0;

        foreach ($this->results as $result) {
            $total += $result->durationInSeconds;
        }

        return $total;
    }

    public function isEmpty(): bool
    {
        return $this->results === [];
    }

    public function count(): int
    {
        return count($this->results);
    }

    /** @return Traversable<int, TestDurationResult> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }
}
