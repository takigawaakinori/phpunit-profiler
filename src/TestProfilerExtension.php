<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class TestProfilerExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $topCount = $this->resolveTopCount($parameters);
        $showTopN = ! ($parameters->has('showTopN')
            && $parameters->get('showTopN') === 'false');
        $showPareto = $parameters->has('showPareto')
            && $parameters->get('showPareto') === 'true';
        $slowThreshold = $this->resolveSlowThreshold($parameters);
        $jsonOutput = $parameters->has('jsonOutput')
            ? $parameters->get('jsonOutput')
            : null;

        $collector = new TestTimeCollector();
        $jsonWriter = $jsonOutput !== null ? new JsonResultWriter($jsonOutput) : null;
        $outputter = new TestDurationOutputter(
            topCount: $topCount,
            showTopN: $showTopN,
            showPareto: $showPareto,
            slowThreshold: $slowThreshold,
        );

        $facade->registerSubscribers(
            new class ($collector) implements PreparedSubscriber {
                public function __construct(private readonly TestTimeCollector $collector) {}

                public function notify(Prepared $event): void
                {
                    $this->collector->recordStart(
                        $event->test()->id(),
                        $event->telemetryInfo()->time(),
                    );
                }
            },
            new class ($collector) implements FinishedSubscriber {
                public function __construct(private readonly TestTimeCollector $collector) {}

                public function notify(Finished $event): void
                {
                    $this->collector->recordEnd(
                        $event->test()->id(),
                        $event->telemetryInfo()->time(),
                    );
                }
            },
            new class ($collector, $outputter, $jsonWriter) implements ExecutionFinishedSubscriber {
                public function __construct(
                    private readonly TestTimeCollector $collector,
                    private readonly TestDurationOutputter $outputter,
                    private readonly ?JsonResultWriter $jsonWriter,
                ) {}

                public function notify(ExecutionFinished $event): void
                {
                    $results = $this->collector->getResults();
                    $this->outputter->print($results);

                    $this->jsonWriter?->write($results);
                }
            },
        );
    }

    private function resolveTopCount(ParameterCollection $parameters): int
    {
        if (! $parameters->has('topCount')) {
            return TestDurationOutputter::DEFAULT_TOP_COUNT;
        }

        $rawTopCount = $parameters->get('topCount');
        if (! is_numeric($rawTopCount)) {
            return TestDurationOutputter::DEFAULT_TOP_COUNT;
        }

        $topCount = (int) $rawTopCount;
        if ($topCount < 1) {
            return TestDurationOutputter::DEFAULT_TOP_COUNT;
        }

        return $topCount;
    }

    private function resolveSlowThreshold(ParameterCollection $parameters): ?float
    {
        if (! $parameters->has('slowThreshold')) {
            return null;
        }

        $rawSlowThreshold = $parameters->get('slowThreshold');
        if (! is_numeric($rawSlowThreshold)) {
            return null;
        }

        return (float) $rawSlowThreshold;
    }
}
