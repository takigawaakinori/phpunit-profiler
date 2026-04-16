# PHPUnit Profiler

PHPUnit のテスト実行時間を計測し、遅いテストをレポートする PHPUnit 拡張です。

PHPUnit 10 / 11 / 12 / 13 に対応しています。

## インストール

```bash
composer require --dev takigawaakinori/phpunit-profiler
```

## 使い方

`phpunit.xml`（または `phpunit.xml.dist`）に拡張を追加します。

```xml
<extensions>
    <bootstrap class="TakigawaAkinori\PhpunitProfiler\TestProfilerExtension"/>
</extensions>
```

## 設定

`<parameter>` を追加すると挙動をカスタマイズできます。

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

| パラメータ | 型 | デフォルト | 説明 |
|-----------|----|-----------|------|
| `showTopN` | bool | `true` | 上位 N 件の遅いテスト一覧を表示。`false` で非表示 |
| `topCount` | int | `20` | 表示する遅いテスト件数 |
| `showPareto` | bool | `false` | パレート分析（上位 20% のテストが全体時間に占める割合）を表示 |
| `slowThreshold` | float | _(無効)_ | 指定秒数以上かかったテストを表示 |
| `jsonOutput` | string | _(無効)_ | 全テストの実行時間を JSON として出力するファイルパス |

### 不正なパラメータ値

- `topCount`: 非数値または `1` 未満の場合、デフォルトの `20` が使われます。
- `slowThreshold`: 非数値の場合、しきい値フィルタは無効になります。
- `showTopN` / `showPareto`: 文字列フラグです。`true` / `false` を明示してください。

## 出力例

テスト実行後、次のような出力が表示されます。

```text
Top 20 Slowest Tests:
--------------------------------------------------------------------------------
  1.  5.032s  Tests\Feature\ExampleTest::test_heavy_operation
  2.  3.210s  Tests\Feature\ExampleTest::test_api_call
  3.  0.523s  Tests\Unit\ExampleTest::test_calculation
--------------------------------------------------------------------------------
```

## 要件

- PHP 8.1 以上
- PHPUnit 10.0 以上

## ライセンス

MIT
