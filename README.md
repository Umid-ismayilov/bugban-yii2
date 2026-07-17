# bugban/yii2

Yii2 extension for the [Bugban](https://bugban.online) error & monitoring SDK.
It auto-captures unhandled exceptions by swapping in a Bugban-aware
`errorHandler` component and initializes the framework-agnostic
`bugban/php-sdk` core for you.

Compatible with `yiisoft/yii2` **>=2.0** and PHP **7.0+**.

## Install

```bash
composer require bugban/yii2
```

## Configure — web app (`config/web.php`)

Register the bootstrap and swap the `errorHandler` component:

```php
return [
    // ...
    'bootstrap' => ['bugban'],
    'components' => [
        'bugban' => ['class' => \Bugban\Yii2\Bootstrap::class],
        'errorHandler' => ['class' => \Bugban\Yii2\WebErrorHandler::class],
    ],
    'params' => [
        'bugban' => [
            'api_key' => 'bb_xxx',
            'host' => 'https://bugban.online',
            'environment' => 'production',
            // 'capture_queries' => true,   // slow-query monitoring (default: on)
            // 'slow_query_ms' => 1000,     // report queries slower than this (ms)
        ],
    ],
];
```

**Slow query monitoring** — automatic: at the end of each request the
extension reads Yii's built-in DB profiling (`yii\db\Command::query` /
`::execute`, enabled by default via `Connection::$enableProfiling`) and
reports queries slower than `slow_query_ms` to Bugban in one non-blocking
batch. Works with any Yii2 DB driver (MySQL, PostgreSQL, SQLite, ...). You
can also record manually:

```php
\Bugban\Sdk\Bugban::recordQuery($sql, $durationMs, ['connection' => 'mysql']);
```

## Configure — console app (`config/console.php`)

```php
return [
    // ...
    'bootstrap' => ['bugban'],
    'components' => [
        'bugban' => ['class' => \Bugban\Yii2\Bootstrap::class],
        'errorHandler' => ['class' => \Bugban\Yii2\ConsoleErrorHandler::class],
    ],
    'params' => [
        'bugban' => [
            'api_key' => 'bb_xxx',
            'host' => 'https://bugban.online',
            'environment' => 'production',
        ],
    ],
];
```

That's it — unhandled exceptions are now reported to Bugban automatically.
The `bugban` component reads its config from `params['bugban']`; it no-ops if
`api_key` is empty.

## Manual capture

You can also report manually anywhere in your app:

```php
use Bugban\Sdk\Bugban;

try {
    // ...
} catch (\Throwable $e) {
    Bugban::capture($e);
}

// Or a message:
Bugban::captureMessage('Something noteworthy happened', 'warning');
```

## Configuration reference

| Key                | Type    | Default                 |
|--------------------|---------|-------------------------|
| `api_key`          | string  | `''`                    |
| `host`             | string  | `https://bugban.online` |
| `environment`      | string  | `production`            |
| `release`          | string  | `null`                  |
| `enabled`          | bool    | `true`                  |
| `capture_requests` | bool    | `false`                 |
| `capture_queries`  | bool    | `true`                  |
| `slow_query_ms`    | int     | `1000`                  |
| `sample_rate`      | float   | `1.0`                   |
| `redact`           | array   | `[]`                    |
| `capture_logs`     | bool    | `false`                 |
| `log_level`        | string  | `error`                 |

## Log capture (errors logged but not thrown)

Errors you catch and log without re-throwing only reach the log file. Enable
`capture_logs` and forward them to Bugban with `recordLog()`:

```php
// In the component config (see above), add:
//   'capture_logs' => true,
//   'log_level'    => 'error',

// Anywhere you'd log an error (e.g. a Yii::error wrapper or directly):
\Bugban\Sdk\Bugban::recordLog('error', 'Queue job failed', ['job' => $id]);

// Caught-and-logged throwable (attach it for a full stacktrace):
try { risky(); } catch (\Throwable $e) {
    \Bugban\Sdk\Bugban::recordLog('error', $e->getMessage(), ['exception' => $e]);
}
```

Records below `log_level` are dropped; context is redacted; `recordLog()` never throws.
