<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

final class TestDurationOutputter
{
    public function printTop20(TestDurationResultCollection $results): void
    {
        if ($results->isEmpty()) {
            return;
        }

        $top = $results->top(20);

        echo PHP_EOL . 'Top 20 Slowest Tests:' . PHP_EOL;
        echo str_repeat('-', 80) . PHP_EOL;

        $rank = 1;
        foreach ($top as $result) {
            echo sprintf(
                ' %2d. %6.3fs  %s',
                $rank,
                $result->durationInSeconds,
                $result->testId,
            ) . PHP_EOL;
            $rank++;
        }

        echo str_repeat('-', 80) . PHP_EOL;
    }

    public function printPareto(TestDurationResultCollection $results): void
    {
        if ($results->isEmpty()) {
            return;
        }

        $totalDuration = $results->totalDuration();
        $top20Percent = $results->topPercentile(20);
        $top20Duration = $top20Percent->totalDuration();
        $percentage = $totalDuration > 0.0 ? ($top20Duration / $totalDuration) * 100 : 0.0;

        echo PHP_EOL . sprintf(
            'Pareto: Top 20%% of tests (%d / %d) account for %.1f%% of total execution time (%.3fs / %.3fs)',
            count($top20Percent),
            count($results),
            $percentage,
            $top20Duration,
            $totalDuration,
        ) . PHP_EOL;
        echo str_repeat('-', 80) . PHP_EOL;

        $rank = 1;
        foreach ($top20Percent as $result) {
            $share = $totalDuration > 0.0 ? ($result->durationInSeconds / $totalDuration) * 100 : 0.0;
            echo sprintf(
                ' %2d. %6.3fs  (%5.1f%%)  %s',
                $rank,
                $result->durationInSeconds,
                $share,
                $result->testId,
            ) . PHP_EOL;
            $rank++;
        }

        echo str_repeat('-', 80) . PHP_EOL;
    }
}
