<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests;

use PHPUnit\Framework\TestCase;
use TakigawaAkinori\PhpunitProfiler\JsonResultWriter;
use TakigawaAkinori\PhpunitProfiler\TestDurationResult;
use TakigawaAkinori\PhpunitProfiler\TestDurationResultCollection;

class JsonResultWriterTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        $this->outputPath = sys_get_temp_dir() . '/phpunit-profile-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputPath)) {
            unlink($this->outputPath);
        }
    }

    public function test_writes_json_file(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('App\\Tests\\FooTest::test_slow', 1.234),
            new TestDurationResult('App\\Tests\\FooTest::test_fast', 0.001),
        );

        $writer = new JsonResultWriter($this->outputPath);
        $writer->write($results);

        $this->assertFileExists($this->outputPath);

        $data = json_decode(file_get_contents($this->outputPath), true);

        $this->assertCount(2, $data);
        $this->assertSame('App\\Tests\\FooTest::test_slow', $data[0]['testId']);
        $this->assertSame(1.234, $data[0]['durationInSeconds']);
        $this->assertSame('App\\Tests\\FooTest::test_fast', $data[1]['testId']);
        $this->assertSame(0.001, $data[1]['durationInSeconds']);
    }

    public function test_writes_empty_array_for_empty_results(): void
    {
        $results = new TestDurationResultCollection();

        $writer = new JsonResultWriter($this->outputPath);
        $writer->write($results);

        $data = json_decode(file_get_contents($this->outputPath), true);

        $this->assertSame([], $data);
    }
}
