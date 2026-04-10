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
        $collector = new TestTimeCollector();
        $outputter = new TestDurationOutputter();

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
            new class ($collector, $outputter) implements ExecutionFinishedSubscriber {
                public function __construct(
                    private readonly TestTimeCollector $collector,
                    private readonly TestDurationOutputter $outputter,
                ) {}

                public function notify(ExecutionFinished $event): void
                {
                    $results = $this->collector->getResults();
                    $this->outputter->printTop20($results);
                }
            },
        );
    }
}
