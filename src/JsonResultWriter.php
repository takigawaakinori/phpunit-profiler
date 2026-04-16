<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

use Closure;

final class JsonResultWriter
{
    /** @var null|Closure(string):void */
    private readonly ?Closure $errorReporter;

    public function __construct(
        private readonly string $outputPath,
        ?Closure $errorReporter = null,
    ) {
        $this->errorReporter = $errorReporter;
    }

    public function write(TestDurationResultCollection $results): void
    {
        $data = [];

        foreach ($results as $result) {
            $data[] = [
                'testId' => $result->testId,
                'durationInSeconds' => $result->durationInSeconds,
            ];
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->reportError(sprintf(
                '[phpunit-profiler] Failed to encode JSON for "%s": %s',
                $this->outputPath,
                json_last_error_msg(),
            ));
            return;
        }

        $written = @file_put_contents($this->outputPath, $json . "\n");
        if ($written === false) {
            $this->reportError(sprintf(
                '[phpunit-profiler] Failed to write JSON output to "%s".',
                $this->outputPath,
            ));
        }
    }

    private function reportError(string $message): void
    {
        if ($this->errorReporter !== null) {
            ($this->errorReporter)($message);
            return;
        }

        // Red background + white foreground for error visibility.
        fwrite(STDERR, "\033[37;41m{$message}\033[0m" . PHP_EOL);
    }
}
