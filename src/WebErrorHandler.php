<?php

namespace Bugban\Yii2;

use Bugban\Sdk\Bugban;

/**
 * Drop-in replacement for Yii2's web error handler that forwards every
 * logged exception to Bugban. Swap it into config/web.php:
 *
 *     'components' => [
 *         'errorHandler' => ['class' => \Bugban\Yii2\WebErrorHandler::class],
 *     ],
 */
class WebErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @param \Throwable|\Exception $exception
     * @return void
     */
    public function logException($exception)
    {
        parent::logException($exception);

        try {
            // Reached Yii's error handler = nobody caught it → unhandled.
            // Guarded: an older core without captureUnhandled() degrades to capture().
            if (method_exists('Bugban\\Sdk\\Bugban', 'captureUnhandled')) {
                Bugban::captureUnhandled($exception);
            } else {
                Bugban::capture($exception);
            }
        } catch (\Exception $e) {
            // never let reporting break Yii's own error handling
        } catch (\Throwable $e) {
            // PHP 7+ fatal errors
        }
    }
}
