<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

final class TestDurationResult
{
    public function __construct(
        public readonly string $testId,
        public readonly float $durationInSeconds,
        public readonly ?string $filePath = null,
    ) {}
}
