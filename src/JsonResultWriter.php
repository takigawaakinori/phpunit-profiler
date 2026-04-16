<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

final class JsonResultWriter
{
    public function __construct(
        private readonly string $outputPath,
    ) {}

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

        file_put_contents($this->outputPath, $json . "\n");
    }
}
