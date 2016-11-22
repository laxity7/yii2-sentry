<?php

namespace laxity7\sentry;

/**
 * Class SentryHelper
 */
class SentryHelper
{
    /**
     * Appends additional context
     *
     * For example,
     *
     * ```php
     * SentryHelper::extraData([$task->attributes]);
     * throw new Exception('unknown task type');
     * ```
     *
     * @param array $data Associative array of extra data
     *
     * @return bool
     */
    public static function extraData($data)
    {
        $ravenClient = self::getRavenClient();
        if (!$ravenClient) {
            return false;
        }

        $ravenClient->extra_context($data);

        return true;
    }

    /**
     * Clear additional context
     * @return bool
     */
    public static function clearExtraData()
    {
        $ravenClient = self::getRavenClient();
        if (!$ravenClient) {
            return false;
        }

        $ravenClient->context->clear();

        return true;
    }

    /**
     * Log an exception with a text message to sentry
     *
     * For example,
     *
     * ```php
     * try {
     *     throw new Exception('FAIL');
     * } catch (Exception $e) {
     *     SentryHelper::captureWithMessage('Fail to save model', $e);
     * }
     * ```
     *
     * @param string          $message           Text message of an exception
     * @param null|\Exception $previousException Instance class of a previous exception (exception which will be
     *                                           expanded)
     * @param string          $level             One of Raven_Client::* levels
     * @param string          $exceptionClass    Base exception class
     *
     * @return bool
     */
    public static function captureWithMessage(
        $message,
        $previousException = null,
        $level = \Raven_Client::ERROR,
        $exceptionClass = 'yii\base\Exception'
    ) {
        $ravenClient = self::getRavenClient();
        if (!$ravenClient) {
            return false;
        }

        $ravenClient->captureException(new $exceptionClass($message, 0, $previousException), ['level' => $level]);

        return true;
    }

    /**
     * Log an exception to sentry
     *
     * @param \Exception $exception The Exception object
     * @param string     $level     One of Raven_Client::* levels
     *
     * @return bool
     */
    public static function captureException($exception, $level = \Raven_Client::ERROR)
    {
        $ravenClient = self::getRavenClient();
        if (!$ravenClient) {
            return false;
        }

        $ravenClient->captureException($exception, ['level' => $level]);

        return true;
    }

    /**
     * Get instance of the Raven Client
     *
     * @return \Raven_Client|null
     */
    public static function getRavenClient()
    {
        $raven = \Yii::$app->get('raven', false);
        if ($raven instanceof ErrorHandler) {
            return $raven->getClient();
        } else {
            return null;
        }
    }
}
