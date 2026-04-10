<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

use PHPUnit\Event\Telemetry\HRTime;

final class TestTimeCollector
{
    /** @var array<string, HRTime> */
    private array $startTimes = [];

    /** @var array<string, float> */
    private array $durations = [];

    public function recordStart(string $testId, HRTime $time): void
    {
        $this->startTimes[$testId] = $time;
    }

    public function recordEnd(string $testId, HRTime $time): void
    {
        if (! isset($this->startTimes[$testId])) {
            return;
        }

        $duration = $time->duration($this->startTimes[$testId]);
        $this->durations[$testId] = $duration->asFloat();

        unset($this->startTimes[$testId]);
    }

    public function getResults(): TestDurationResultCollection
    {
        arsort($this->durations);

        $results = [];

        foreach ($this->durations as $testId => $seconds) {
            $results[] = new TestDurationResult($testId, $seconds);
        }

        return new TestDurationResultCollection(...$results);
    }
}
