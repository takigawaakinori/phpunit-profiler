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

    /** @var array<string, string> */
    private array $filePaths = [];

    public function recordStart(string $testId, HRTime $time, ?string $filePath = null): void
    {
        $this->startTimes[$testId] = $time;

        if ($filePath !== null) {
            $this->filePaths[$testId] = $filePath;
        }
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
            $results[] = new TestDurationResult(
                $testId,
                $seconds,
                $this->filePaths[$testId] ?? null,
            );
        }

        return new TestDurationResultCollection(...$results);
    }
}
