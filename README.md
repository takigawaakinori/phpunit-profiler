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

## Configuration

You can customize the extension behavior by adding `<parameter>` elements:

```xml
<extensions>
    <bootstrap class="TakigawaAkinori\PhpunitProfiler\TestProfilerExtension">
        <parameter name="showTopN" value="false"/>
        <parameter name="topCount" value="50"/>
        <parameter name="showPareto" value="true"/>
        <parameter name="slowThreshold" value="1.0"/>
        <parameter name="jsonOutput" value=".test-profile-result.json"/>
    </bootstrap>
</extensions>
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `showTopN` | bool | `true` | Show the top N slowest tests list. Set to `false` to hide |
| `topCount` | int | `20` | Number of slowest tests to display |
| `showPareto` | bool | `false` | Show Pareto analysis (top 20% of tests and their share of total execution time) |
| `slowThreshold` | float | _(disabled)_ | Show tests slower than the specified threshold in seconds |
| `jsonOutput` | string | _(disabled)_ | File path to export all test durations as JSON |

## Output Examples

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
