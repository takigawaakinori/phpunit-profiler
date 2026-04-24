<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler\Tests;

use PHPUnit\Framework\TestCase;
use TakigawaAkinori\PhpunitProfiler\HtmlResultWriter;
use TakigawaAkinori\PhpunitProfiler\TestDurationResult;
use TakigawaAkinori\PhpunitProfiler\TestDurationResultCollection;

class HtmlResultWriterTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . '/phpunit-profile-html-' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $this->removeTree($this->outputDir);
        }
    }

    public function test_writes_index_and_per_file_pages(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('App\\Tests\\FooTest::test_slow', 1.000, '/repo/tests/FooTest.php'),
            new TestDurationResult('App\\Tests\\FooTest::test_fast', 0.250, '/repo/tests/FooTest.php'),
            new TestDurationResult('App\\Tests\\BarTest::test_medium', 0.750, '/repo/tests/Integration/BarTest.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        $this->assertFileExists($this->outputDir . '/index.html');
        $this->assertFileExists($this->outputDir . '/FooTest.php.html');
        $this->assertFileExists($this->outputDir . '/Integration/index.html');
        $this->assertFileExists($this->outputDir . '/Integration/BarTest.php.html');
    }

    public function test_index_page_aggregates_by_folder_with_percentages(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Foo::test_a', 1.000, '/repo/tests/FooTest.php'),
            new TestDurationResult('Bar::test_a', 3.000, '/repo/tests/Integration/BarTest.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        $index = (string) file_get_contents($this->outputDir . '/index.html');

        // Grand total = 4.0s. Integration folder = 3.0s → 75%. FooTest.php = 1.0s → 25%.
        $this->assertStringContainsString('Integration', $index);
        $this->assertStringContainsString('FooTest.php', $index);
        $this->assertStringContainsString('75.00', $index);
        $this->assertStringContainsString('25.00', $index);

        // Directory link and file link.
        $this->assertStringContainsString('href="Integration/index.html"', $index);
        $this->assertStringContainsString('href="FooTest.php.html"', $index);
    }

    public function test_file_page_lists_tests_with_durations(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('App\\FooTest::test_slow', 2.000, '/repo/tests/FooTest.php'),
            new TestDurationResult('App\\FooTest::test_fast', 0.500, '/repo/tests/FooTest.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        $filePage = (string) file_get_contents($this->outputDir . '/FooTest.php.html');

        $this->assertStringContainsString('App\\FooTest::test_slow', $filePage);
        $this->assertStringContainsString('App\\FooTest::test_fast', $filePage);
        // test_slow is 80% of 2.5s total, test_fast is 20%.
        $this->assertStringContainsString('80.00', $filePage);
        $this->assertStringContainsString('20.00', $filePage);
        // Breadcrumb link back to root.
        $this->assertStringContainsString('href="index.html"', $filePage);
    }

    public function test_deep_folder_gets_nested_directory_pages(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('A::t', 1.0, '/repo/tests/Feature/Auth/LoginTest.php'),
            new TestDurationResult('B::t', 1.0, '/repo/tests/Feature/Auth/LogoutTest.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        // Common root is /repo/tests/Feature/Auth → index.html at output root shows LoginTest.php / LogoutTest.php.
        $index = (string) file_get_contents($this->outputDir . '/index.html');
        $this->assertStringContainsString('LoginTest.php', $index);
        $this->assertStringContainsString('LogoutTest.php', $index);
        $this->assertFileExists($this->outputDir . '/LoginTest.php.html');
        $this->assertFileExists($this->outputDir . '/LogoutTest.php.html');
    }

    public function test_common_root_shrinks_when_siblings_are_in_different_directories(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('A::t', 1.0, '/repo/tests/Unit/Alpha.php'),
            new TestDurationResult('B::t', 1.0, '/repo/tests/Integration/Beta.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        // Common root is /repo/tests → index.html should show Unit/ and Integration/ as children.
        $index = (string) file_get_contents($this->outputDir . '/index.html');
        $this->assertStringContainsString('href="Unit/index.html"', $index);
        $this->assertStringContainsString('href="Integration/index.html"', $index);
        $this->assertFileExists($this->outputDir . '/Unit/Alpha.php.html');
        $this->assertFileExists($this->outputDir . '/Integration/Beta.php.html');
    }

    public function test_breadcrumbs_from_nested_file_link_back_to_root(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('A::t', 1.0, '/repo/tests/Unit/Alpha.php'),
            new TestDurationResult('B::t', 1.0, '/repo/tests/Integration/Beta.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        $nestedPage = (string) file_get_contents($this->outputDir . '/Integration/Beta.php.html');

        // From Integration/Beta.php.html the root is one level up.
        $this->assertStringContainsString('href="../index.html"', $nestedPage);
    }

    public function test_writes_empty_index_when_no_results(): void
    {
        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write(new TestDurationResultCollection());

        $index = (string) file_get_contents($this->outputDir . '/index.html');
        $this->assertStringContainsString('No test durations were recorded', $index);
    }

    public function test_skips_results_without_file_path_but_renders_rest(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('Unknown::test', 9.999, null),
            new TestDurationResult('App\\FooTest::test_a', 1.000, '/repo/tests/FooTest.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        $index = (string) file_get_contents($this->outputDir . '/index.html');
        $this->assertStringContainsString('FooTest.php', $index);
        $this->assertStringNotContainsString('Unknown::test', $index);
    }

    public function test_html_is_properly_escaped(): void
    {
        $results = new TestDurationResultCollection(
            new TestDurationResult('App\\<script>::test', 1.0, '/repo/tests/FooTest.php'),
        );

        $writer = new HtmlResultWriter($this->outputDir);
        $writer->write($results);

        $filePage = (string) file_get_contents($this->outputDir . '/FooTest.php.html');
        $this->assertStringNotContainsString('<script>', $filePage);
        $this->assertStringContainsString('&lt;script&gt;', $filePage);
    }

    public function test_reports_error_when_output_directory_cannot_be_created(): void
    {
        // Create a file where the directory would need to be; mkdir will fail.
        $conflicting = sys_get_temp_dir() . '/phpunit-profile-html-conflict-' . uniqid();
        file_put_contents($conflicting, 'blocker');

        $errors = [];
        $writer = new HtmlResultWriter(
            $conflicting . '/nested',
            static function (string $message) use (&$errors): void {
                $errors[] = $message;
            },
        );

        $writer->write(new TestDurationResultCollection(
            new TestDurationResult('A::t', 1.0, '/repo/tests/Foo.php'),
        ));

        @unlink($conflicting);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Failed to create HTML output directory', $errors[0]);
    }

    private function removeTree(string $path): void
    {
        if (! is_dir($path)) {
            if (file_exists($path)) {
                @unlink($path);
            }
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $this->removeTree($path . '/' . $entry);
        }

        @rmdir($path);
    }
}
