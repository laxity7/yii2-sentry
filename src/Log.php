<?php

namespace laxity7\sentry;

use Yii;

/**
 * Class Log
 */
class Log
{

    /**
     * Sends exception to Sentry using raven component
     *
     * @param string          $message   The message to be logged.
     * @param string          $category  The category of the message.
     * @param \Exception|null $exception The Exception object
     */
    public static function sendException($message, $category, $exception)
    {
        if ($exception !== null && $ravenClient = SentryHelper::getRavenClient()) {
            $ravenClient->extra_context($message);
            $ravenClient->captureException($exception);
        }
    }

    /**
     * Proxy for Yii::error method, that also sends exception data to Sentry
     *
     * @param string          $message   The message to be logged.
     * @param string          $category  The category of the message.
     * @param \Exception|null $exception The Exception object
     */
    public static function error($message, $category = 'application', $exception = null)
    {
        Yii::error($message, $category);
        self::sendException($message, $category, $exception);
    }

    /**
     * Proxy for Yii::warning method, that also sends exception data to Sentry
     *
     * @param string          $message   The message to be logged.
     * @param string          $category  The category of the message.
     * @param \Exception|null $exception The Exception object
     */
    public static function warning($message, $category = 'application', $exception = null)
    {
        Yii::warning($message, $category);
        self::sendException($message, $category, $exception);
    }

    /**
     * Proxy for Yii::info method, that also sends exception data to Sentry
     *
     * @param string          $message   The message to be logged.
     * @param string          $category  The category of the message.
     * @param \Exception|null $exception The Exception object
     */
    public static function info($message, $category = 'application', $exception = null)
    {
        Yii::info($message, $category);
        self::sendException($message, $category, $exception);
    }

    /**
     * Proxy for Yii::trace method, that also sends exception data to Sentry
     *
     * @param string          $message   The message to be logged.
     * @param string          $category  The category of the message.
     * @param \Exception|null $exception The Exception object
     */
    public static function trace($message, $category = 'application', $exception = null)
    {
        Yii::trace($message, $category);
        self::sendException($message, $category, $exception);
    }

}