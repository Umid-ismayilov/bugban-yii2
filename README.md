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
        ],
    ],
];
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
| `sample_rate`      | float   | `1.0`                   |
| `redact`           | array   | `[]`                    |
