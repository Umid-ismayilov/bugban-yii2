<?php

namespace Bugban\Yii2;

use Bugban\Sdk\Bugban;
use yii\base\BootstrapInterface;

/**
 * Bugban Yii2 bootstrap. Register in config/web.php (or console.php):
 *
 *     'bootstrap' => ['bugban'],
 *     'components' => [
 *         'bugban' => ['class' => \Bugban\Yii2\Bootstrap::class],
 *     ],
 *     'params' => ['bugban' => ['api_key' => 'bb_xxx']],
 *
 * Reads its config from $app->params['bugban'] and initializes the
 * framework-agnostic bugban/php-sdk core.
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @param \yii\base\Application $app
     * @return void
     */
    public function bootstrap($app)
    {
        $params = isset($app->params['bugban']) ? $app->params['bugban'] : array();
        if (!is_array($params)) {
            return;
        }

        $apiKey = isset($params['api_key']) ? $params['api_key'] : '';
        if ($apiKey === '' || $apiKey === null) {
            return; // no-op when no API key is configured
        }

        // Metadata for the one-time install ping (SDK handshake).
        if (!isset($params['framework'])) {
            $params['framework'] = 'yii2';
        }
        if (!isset($params['framework_version'])) {
            try {
                if (class_exists('Yii') && method_exists('Yii', 'getVersion')) {
                    $params['framework_version'] = \Yii::getVersion();
                }
            } catch (\Exception $e) {
                // ignore
            } catch (\Throwable $e) {
                // ignore
            }
        }
        if (!isset($params['app_name'])) {
            try {
                if (isset($app->name) && is_string($app->name) && $app->name !== '') {
                    $params['app_name'] = $app->name;
                }
            } catch (\Exception $e) {
                // ignore
            } catch (\Throwable $e) {
                // ignore
            }
        }
        if (!isset($params['sdk'])) {
            $params['sdk'] = 'bugban/yii2';
        }

        Bugban::init($params);
        $this->registerQueryCapture($app, $params);
        $this->registerQueryRunner($app);
    }

    /**
     * Let the Bugban panel re-run one of this app's own captured SELECTs and
     * report the timing, so a developer can confirm an index actually helped.
     * Runs on Yii's own connection inside a transaction that is always rolled
     * back, and returns only the row COUNT — no row data ever leaves here.
     *
     * @param \yii\base\Application $app
     * @return void
     */
    private function registerQueryRunner($app)
    {
        try {
            if (!is_object($app) || !isset($app->db)) {
                return;
            }
            $db = $app->db;
            Bugban::setQueryRunner(function ($sql, array $bindings) use ($db) {
                $transaction = $db->beginTransaction();
                try {
                    $rows = $db->createCommand($sql, $bindings)->queryAll();

                    return is_array($rows) ? count($rows) : 0;
                } catch (\Exception $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    throw $e;
                } finally {
                    try {
                        $transaction->rollBack();
                    } catch (\Exception $e) {
                        // Nothing was written; a failed rollback is not fatal.
                    }
                }
            });
        } catch (\Exception $e) {
            // Monitoring must never break the app.
        } catch (\Throwable $e) {
            // Same for engine errors.
        }
    }

    /**
     * Slow-query capture: read Yii's built-in DB profiling at the end of the
     * request (EVENT_AFTER_REQUEST fires before the logger flushes, so the
     * profiling messages are still in memory). Durations are seconds -> ms;
     * the SDK drops anything faster than the configured slow_query_ms.
     * Fully guarded — never throws, never breaks the host app.
     *
     * @param \yii\base\Application $app
     * @param array $params
     * @return void
     */
    private function registerQueryCapture($app, array $params)
    {
        try {
            if (isset($params['capture_queries']) && !$params['capture_queries']) {
                return;
            }
            if (!is_object($app) || !method_exists($app, 'on')) {
                return;
            }
            $app->on(\yii\base\Application::EVENT_AFTER_REQUEST, function () {
                try {
                    if (!class_exists('Yii', false)) {
                        return;
                    }
                    $logger = \Yii::getLogger();
                    if (!is_object($logger) || !method_exists($logger, 'getProfiling')) {
                        return;
                    }
                    $profiling = $logger->getProfiling(array('yii\db\Command::query', 'yii\db\Command::execute'));
                    if (!is_array($profiling)) {
                        return;
                    }
                    foreach ($profiling as $entry) {
                        if (!isset($entry['info'], $entry['duration'])) {
                            continue;
                        }
                        $meta = array();
                        // Logger traces (when traceLevel > 0) give us the app caller.
                        if (isset($entry['trace'][0]['file']) && is_string($entry['trace'][0]['file'])) {
                            $meta['file'] = $entry['trace'][0]['file'];
                            if (isset($entry['trace'][0]['line'])) {
                                $meta['line'] = $entry['trace'][0]['line'];
                            }
                        }
                        Bugban::recordQuery((string) $entry['info'], ((float) $entry['duration']) * 1000, $meta);
                    }
                } catch (\Exception $e) {
                    // never break the host app
                } catch (\Throwable $e) {
                    // non-fatal
                }
            });
        } catch (\Exception $e) {
            // never break the host app
        } catch (\Throwable $e) {
            // non-fatal
        }
    }
}
