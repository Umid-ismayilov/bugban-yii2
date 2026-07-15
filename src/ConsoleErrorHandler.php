<?php

namespace Bugban\Yii2;

use Bugban\Sdk\Bugban;

/**
 * Drop-in replacement for Yii2's console error handler that forwards every
 * logged exception to Bugban. Swap it into config/console.php:
 *
 *     'components' => [
 *         'errorHandler' => ['class' => \Bugban\Yii2\ConsoleErrorHandler::class],
 *     ],
 */
class ConsoleErrorHandler extends \yii\console\ErrorHandler
{
    /**
     * @param \Throwable|\Exception $exception
     * @return void
     */
    public function logException($exception)
    {
        parent::logException($exception);

        try {
            Bugban::capture($exception);
        } catch (\Exception $e) {
            // never let reporting break Yii's own error handling
        } catch (\Throwable $e) {
            // PHP 7+ fatal errors
        }
    }
}
