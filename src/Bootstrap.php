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

        Bugban::init($params);
    }
}
