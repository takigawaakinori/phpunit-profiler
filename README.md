# PHPUnit Profiler

A PHPUnit extension that profiles test execution time and reports the slowest tests.

Supports PHPUnit 10, 11, 12, and 13.

## Installation

```bash
composer require --dev takigawaakinori/phpunit-profiler
```

## Usage

Add the extension to your `phpunit.xml` (or `phpunit.xml.dist`):

```xml
<extensions>
    <bootstrap class="TakigawaAkinori\PhpunitProfiler\TestProfilerExtension"/>
</extensions>
```

After running your tests, you'll see output like:

```
Top 20 Slowest Tests:
--------------------------------------------------------------------------------
  1.  5.032s  Tests\Feature\ExampleTest::test_heavy_operation
  2.  3.210s  Tests\Feature\ExampleTest::test_api_call
  3.  0.523s  Tests\Unit\ExampleTest::test_calculation
--------------------------------------------------------------------------------
```

## Requirements

- PHP 8.1+
- PHPUnit 10.0+

## License

MIT
