<?php

declare(strict_types=1);

namespace TakigawaAkinori\PhpunitProfiler;

use Closure;

final class HtmlResultWriter
{
    /** @var null|Closure(string):void */
    private readonly ?Closure $errorReporter;

    public function __construct(
        private readonly string $outputDir,
        ?Closure $errorReporter = null,
    ) {
        $this->errorReporter = $errorReporter;
    }

    public function write(TestDurationResultCollection $results): void
    {
        if (! $this->ensureDirectory($this->outputDir)) {
            return;
        }

        $withPaths = [];
        foreach ($results as $result) {
            if ($result->filePath !== null && $result->filePath !== '') {
                $withPaths[] = $result;
            }
        }

        if (count($withPaths) === 0) {
            $this->writeHtmlFile(
                $this->outputDir . '/index.html',
                $this->renderEmptyIndex(),
            );
            return;
        }

        $grandTotal = 0.0;
        foreach ($withPaths as $result) {
            $grandTotal += $result->durationInSeconds;
        }

        $rootSegments = $this->commonRootSegments(array_map(
            static fn(TestDurationResult $r): string => (string) $r->filePath,
            $withPaths,
        ));

        $tree = $this->emptyDirNode($this->rootLabel($rootSegments));

        foreach ($withPaths as $result) {
            $segments = $this->relativeSegments((string) $result->filePath, $rootSegments);
            if ($segments === []) {
                continue;
            }
            $this->insertIntoTree($tree, $segments, $result);
        }

        $this->aggregate($tree);
        $this->renderNode($tree, [], $grandTotal);
    }

    /**
     * @param array<string, mixed> $node
     * @param list<string>         $pathSegments path from report root to this node (empty = root)
     */
    private function renderNode(array $node, array $pathSegments, float $grandTotal): void
    {
        if ($node['type'] === 'file') {
            $this->renderFilePage($node, $pathSegments, $grandTotal);
            return;
        }

        $this->renderDirectoryPage($node, $pathSegments, $grandTotal);

        foreach ($node['children'] as $childName => $childNode) {
            $childSegments = $pathSegments;
            $childSegments[] = (string) $childName;
            $this->renderNode($childNode, $childSegments, $grandTotal);
        }
    }

    /**
     * @param array<string, mixed> $node
     * @param list<string>         $pathSegments
     */
    private function renderDirectoryPage(array $node, array $pathSegments, float $grandTotal): void
    {
        $depth = count($pathSegments);

        $children = $node['children'];
        uasort($children, static fn(array $a, array $b): int => $b['duration'] <=> $a['duration']);

        $rowsHtml = '';
        foreach ($children as $childName => $childNode) {
            $childName = (string) $childName;
            $share = $grandTotal > 0.0 ? $childNode['duration'] / $grandTotal * 100.0 : 0.0;
            $href = $childNode['type'] === 'dir'
                ? $this->encodeUrlSegment($childName) . '/index.html'
                : $this->encodeUrlSegment($childName) . '.html';
            $icon = $childNode['type'] === 'dir' ? 'DIR' : 'FILE';

            $rowsHtml .= sprintf(
                '<tr><td><span class="badge badge-%s">%s</span> <a href="%s">%s</a></td>'
                    . '<td class="num">%d</td><td class="num">%s</td><td class="num">%s%%</td><td>%s</td></tr>',
                $childNode['type'] === 'dir' ? 'dir' : 'file',
                $icon,
                $this->escape($href),
                $this->escape($childName),
                $childNode['count'],
                $this->escape($this->formatDuration($childNode['duration'])),
                $this->escape($this->formatPercent($share)),
                $this->progressBar($share),
            );
        }

        $summaryShare = $grandTotal > 0.0 ? $node['duration'] / $grandTotal * 100.0 : 0.0;
        $title = $pathSegments === []
            ? 'PHPUnit Profile Report'
            : 'Directory: ' . implode('/', $pathSegments);

        $heading = $pathSegments === []
            ? 'PHPUnit Profile Report'
            : end($pathSegments);

        $pathLabel = $pathSegments === []
            ? $node['name']
            : $node['name'] . '/' . implode('/', $pathSegments);

        $body = $this->renderHeader($heading, $pathLabel, $pathSegments, $depth, $node['count'], $node['duration'], $summaryShare)
            . '<table><thead><tr>'
            . '<th>Name</th><th class="num">Tests</th><th class="num">Duration</th><th class="num">Share</th><th>&nbsp;</th>'
            . '</tr></thead><tbody>'
            . $rowsHtml
            . '</tbody></table>';

        $page = $this->renderLayout($title, $body, $depth);

        $dirPath = $this->outputDir;
        if ($pathSegments !== []) {
            $dirPath .= '/' . implode('/', array_map($this->sanitizeFsSegment(...), $pathSegments));
            if (! $this->ensureDirectory($dirPath)) {
                return;
            }
        }

        $this->writeHtmlFile($dirPath . '/index.html', $page);
    }

    /**
     * @param array<string, mixed> $node
     * @param list<string>         $pathSegments
     */
    private function renderFilePage(array $node, array $pathSegments, float $grandTotal): void
    {
        $depth = count($pathSegments) - 1;

        $tests = $node['tests'];
        usort($tests, static fn(TestDurationResult $a, TestDurationResult $b): int
            => $b->durationInSeconds <=> $a->durationInSeconds);

        $rowsHtml = '';
        foreach ($tests as $test) {
            $share = $grandTotal > 0.0 ? $test->durationInSeconds / $grandTotal * 100.0 : 0.0;
            $rowsHtml .= sprintf(
                '<tr><td><code>%s</code></td><td class="num">%s</td><td class="num">%s%%</td><td>%s</td></tr>',
                $this->escape($test->testId),
                $this->escape($this->formatDuration($test->durationInSeconds)),
                $this->escape($this->formatPercent($share)),
                $this->progressBar($share),
            );
        }

        $summaryShare = $grandTotal > 0.0 ? $node['duration'] / $grandTotal * 100.0 : 0.0;
        $fileName = end($pathSegments) ?: $node['name'];
        $pathLabel = $node['filePath'];
        $title = 'File: ' . $fileName;

        $body = $this->renderHeader($fileName, $pathLabel, $pathSegments, $depth, $node['count'], $node['duration'], $summaryShare)
            . '<table><thead><tr>'
            . '<th>Test</th><th class="num">Duration</th><th class="num">Share</th><th>&nbsp;</th>'
            . '</tr></thead><tbody>'
            . $rowsHtml
            . '</tbody></table>';

        $page = $this->renderLayout($title, $body, $depth);

        $parentDir = $this->outputDir;
        $fileSegments = $pathSegments;
        $leafName = array_pop($fileSegments) ?? 'file';
        if ($fileSegments !== []) {
            $parentDir .= '/' . implode('/', array_map($this->sanitizeFsSegment(...), $fileSegments));
            if (! $this->ensureDirectory($parentDir)) {
                return;
            }
        }

        $this->writeHtmlFile(
            $parentDir . '/' . $this->sanitizeFsSegment($leafName) . '.html',
            $page,
        );
    }

    private function renderEmptyIndex(): string
    {
        $body = '<h1>PHPUnit Profile Report</h1>'
            . '<p class="empty">No test durations were recorded.</p>';

        return $this->renderLayout('PHPUnit Profile Report', $body, 0);
    }

    /**
     * @param list<string> $pathSegments
     */
    private function renderHeader(
        string $heading,
        string $pathLabel,
        array $pathSegments,
        int $depth,
        int $count,
        float $duration,
        float $share,
    ): string {
        $crumbsHtml = $this->breadcrumbs($pathSegments, $depth);

        $summary = sprintf(
            '<div class="summary">Tests: <strong>%d</strong>'
                . ' &middot; Duration: <strong>%s</strong>'
                . ' &middot; Share: <strong>%s%%</strong></div>',
            $count,
            $this->escape($this->formatDuration($duration)),
            $this->escape($this->formatPercent($share)),
        );

        return sprintf(
            '<h1>%s</h1><p class="path">%s</p>%s%s',
            $this->escape($heading),
            $this->escape($pathLabel),
            $crumbsHtml,
            $summary,
        );
    }

    /**
     * @param list<string> $pathSegments
     */
    private function breadcrumbs(array $pathSegments, int $depth): string
    {
        $parts = [];

        if ($pathSegments === []) {
            $parts[] = '<span class="crumb-current">root</span>';
        } else {
            $rootHref = str_repeat('../', $depth) . 'index.html';
            $parts[] = sprintf('<a href="%s">root</a>', $this->escape($rootHref));
        }

        $total = count($pathSegments);
        foreach ($pathSegments as $index => $segment) {
            $isLast = ($index === $total - 1);
            if ($isLast) {
                $parts[] = sprintf('<span class="crumb-current">%s</span>', $this->escape($segment));
                continue;
            }

            $up = max(0, $depth - ($index + 1));
            $href = str_repeat('../', $up) . 'index.html';
            $parts[] = sprintf('<a href="%s">%s</a>', $this->escape($href), $this->escape($segment));
        }

        return '<div class="breadcrumbs">' . implode(' <span class="sep">/</span> ', $parts) . '</div>';
    }

    private function progressBar(float $percent): string
    {
        $width = max(0.0, min(100.0, $percent));
        return sprintf(
            '<div class="bar"><div class="bar-fill" style="width: %s%%"></div></div>',
            number_format($width, 2, '.', ''),
        );
    }

    private function formatDuration(float $seconds): string
    {
        if ($seconds >= 1.0) {
            return number_format($seconds, 3) . 's';
        }
        return number_format($seconds * 1000.0, 2) . 'ms';
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 2);
    }

    private function renderLayout(string $title, string $body, int $depth): string
    {
        $css = <<<'CSS'
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 2rem; color: #1f2937; background: #ffffff; }
h1 { margin: 0 0 .25rem; font-size: 1.5rem; }
p.path { margin: 0 0 1rem; color: #6b7280; font-family: ui-monospace, monospace; font-size: .85rem; word-break: break-all; }
.breadcrumbs { font-size: .9rem; margin-bottom: 1rem; }
.breadcrumbs a { color: #2563eb; text-decoration: none; }
.breadcrumbs a:hover { text-decoration: underline; }
.breadcrumbs .sep { color: #9ca3af; margin: 0 .25rem; }
.breadcrumbs .crumb-current { color: #111827; font-weight: 600; }
.summary { background: #f8fafc; padding: .75rem 1rem; border-radius: .375rem; margin-bottom: 1rem; border: 1px solid #e5e7eb; font-size: .95rem; }
table { width: 100%; border-collapse: collapse; font-size: .95rem; }
th { text-align: left; background: #f1f5f9; padding: .5rem .75rem; border-bottom: 2px solid #cbd5e1; font-weight: 600; }
td { padding: .5rem .75rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
td.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
td a { color: #2563eb; text-decoration: none; }
td a:hover { text-decoration: underline; }
code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem; background: #f3f4f6; padding: .1rem .35rem; border-radius: .25rem; word-break: break-all; }
.badge { display: inline-block; font-size: .7rem; padding: .1rem .4rem; border-radius: .25rem; margin-right: .35rem; font-weight: 600; letter-spacing: .05em; }
.badge-dir { background: #dbeafe; color: #1e40af; }
.badge-file { background: #e5e7eb; color: #374151; }
.bar { width: 160px; height: .5rem; background: #e2e8f0; border-radius: .25rem; overflow: hidden; }
.bar-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.empty { color: #6b7280; font-style: italic; }
CSS;

        return "<!DOCTYPE html>\n"
            . '<html lang="en"><head><meta charset="utf-8">'
            . '<title>' . $this->escape($title) . '</title>'
            . '<style>' . $css . '</style>'
            . '</head><body>'
            . $body
            . '</body></html>'
            . "\n";
    }

    /**
     * @param list<string> $filePaths
     * @return list<string> segments of the common root directory (no leading slash)
     */
    private function commonRootSegments(array $filePaths): array
    {
        if ($filePaths === []) {
            return [];
        }

        $directorySegments = array_map(
            fn(string $path): array => $this->splitSegments(dirname($path)),
            $filePaths,
        );

        $common = $directorySegments[0];
        foreach ($directorySegments as $segs) {
            $limit = min(count($common), count($segs));
            $newCommon = [];
            for ($i = 0; $i < $limit; $i++) {
                if ($common[$i] !== $segs[$i]) {
                    break;
                }
                $newCommon[] = $common[$i];
            }
            $common = $newCommon;
            if ($common === []) {
                break;
            }
        }

        return $common;
    }

    /**
     * @return list<string>
     */
    private function splitSegments(string $path): array
    {
        $normalized = str_replace('\\', '/', $path);
        $parts = explode('/', $normalized);

        return array_values(array_filter($parts, static fn(string $p): bool => $p !== ''));
    }

    /**
     * @param list<string> $rootSegments
     * @return list<string>
     */
    private function relativeSegments(string $filePath, array $rootSegments): array
    {
        $segs = $this->splitSegments($filePath);
        $skip = count($rootSegments);

        return array_slice($segs, $skip);
    }

    /**
     * @param list<string> $rootSegments
     */
    private function rootLabel(array $rootSegments): string
    {
        if ($rootSegments === []) {
            return '/';
        }

        return '/' . implode('/', $rootSegments);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyDirNode(string $name): array
    {
        return [
            'type' => 'dir',
            'name' => $name,
            'children' => [],
            'duration' => 0.0,
            'count' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $tree
     * @param list<string>         $segments
     */
    private function insertIntoTree(array &$tree, array $segments, TestDurationResult $result): void
    {
        $cursor = &$tree;
        $lastIndex = count($segments) - 1;

        foreach ($segments as $i => $segment) {
            if ($i === $lastIndex) {
                if (! isset($cursor['children'][$segment])) {
                    $cursor['children'][$segment] = [
                        'type' => 'file',
                        'name' => $segment,
                        'filePath' => (string) $result->filePath,
                        'tests' => [],
                        'duration' => 0.0,
                        'count' => 0,
                    ];
                }
                $cursor['children'][$segment]['tests'][] = $result;
                unset($cursor);
                return;
            }

            if (! isset($cursor['children'][$segment])) {
                $cursor['children'][$segment] = $this->emptyDirNode($segment);
            }
            $cursor = &$cursor['children'][$segment];
        }
    }

    /**
     * @param array<string, mixed> $node
     */
    private function aggregate(array &$node): void
    {
        if ($node['type'] === 'file') {
            $total = 0.0;
            foreach ($node['tests'] as $test) {
                $total += $test->durationInSeconds;
            }
            $node['duration'] = $total;
            $node['count'] = count($node['tests']);
            return;
        }

        $total = 0.0;
        $count = 0;
        foreach ($node['children'] as &$child) {
            $this->aggregate($child);
            $total += $child['duration'];
            $count += $child['count'];
        }
        unset($child);

        $node['duration'] = $total;
        $node['count'] = $count;
    }

    private function ensureDirectory(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        if (@mkdir($path, 0777, true) || is_dir($path)) {
            return true;
        }

        $this->reportError(sprintf(
            '[phpunit-profiler] Failed to create HTML output directory "%s".',
            $path,
        ));

        return false;
    }

    private function writeHtmlFile(string $path, string $contents): void
    {
        $written = @file_put_contents($path, $contents);
        if ($written === false) {
            $this->reportError(sprintf(
                '[phpunit-profiler] Failed to write HTML output to "%s".',
                $path,
            ));
        }
    }

    private function reportError(string $message): void
    {
        if ($this->errorReporter !== null) {
            ($this->errorReporter)($message);
            return;
        }

        fwrite(STDERR, "\033[37;41m{$message}\033[0m" . PHP_EOL);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function encodeUrlSegment(string $segment): string
    {
        return rawurlencode($segment);
    }

    private function sanitizeFsSegment(string $segment): string
    {
        return $segment;
    }
}
