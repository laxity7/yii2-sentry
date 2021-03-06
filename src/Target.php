<?php

namespace laxity7\sentry;

use Raven_Stacktrace;
use yii\base\ErrorException;
use yii\log\Logger;

/**
 * Class Target
 */
class Target extends \yii\log\Target
{
    /**
     * @var string Sentry DSN
     */
    public $dsn;

    /**
     * @var array Raven client options.
     * @see \Raven_Client::__construct for more details
     */
    public $clientOptions = [];

    /**
     * @var \Raven_Client
     */
    protected $client;

    /**
     * @var callable|array|null User context for Raven_Client. Callable must return array and its signature must be as
     *      follows:
     *
     * ```php
     * function ($client)
     * ```
     */
    public $userContext = null;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->client = new \Raven_Client($this->dsn, $this->clientOptions);
        $this->setUserContext();
    }

    /**
     * Set user context for Raven_Client.
     * @see \Raven_Client::user_context
     */
    public function setUserContext()
    {
        if ($this->userContext === null) {
            return;
        }

        if (is_callable($this->userContext)) {
            $this->userContext = call_user_func($this->userContext, $this->client);
        }

        $this->client->user_context($this->userContext);
    }

    /** @inheritdoc */
    protected function getContextMessage()
    {
        return '';
    }

    /**
     * Filter all exceptions. They're logged via ErrorHandler
     * @inheritdoc
     */
    public static function filterMessages($messages, $levels = 0, $categories = [], $except = [])
    {
        $messages = parent::filterMessages($messages, $levels, $categories, $except);
        foreach ($messages as $i => $message) {
            $type = explode(':', $message[2]);
            // shutdown function not working in yii2 yet: https://github.com/yiisoft/yii2/issues/6637
            // allow fatal errors exceptions in log messages
            if (is_array($type) &&
                sizeof($type) == 2 &&
                $type[0] == 'yii\base\ErrorException' &&
                ErrorException::isFatalError(['type' => $type[1]])
            ) {
                continue;
            }
            if (is_string($message[0]) && strpos($message[0], 'exception \'') === 0) {
                unset($messages[$i]);
            }
        }

        return $messages;
    }

    /** @inheritdoc */
    public function export()
    {
        foreach ($this->messages as $message) {
            list($msg, $level, $category, $timestamp, $traces) = $message;
            $new_extras = [];

            $levelName = Logger::getLevelName($level);
            if (!in_array($levelName, ['error', 'warning', 'info'])) {
                $levelName = 'error';
            }

            if (is_array($msg)) {
                if (isset($msg['data'])) {
                    $new_extras = $msg['data'];
                    unset($msg['data']);
                }
                if (isset($msg['msg'])) {
                    $new_msg = $msg['msg'];
                    unset($msg['msg']);
                } else {
                    $new_msg = 'Unknown event format'; // deliver event data even if the format doesn't fit
                    $new_extras = array_merge($new_extras, $msg);
                    $new_tags = ['format' => 'unknown'];
                }
            }

            $data = [
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z', $timestamp),
                'level'     => $levelName,
                'tags'      => ['category' => $category],
                'message'   => isset($new_msg) ? $new_msg : $msg,
            ];

            if (isset($new_tags)) {
                $data['tags'] = array_merge($new_tags, $this->client->get_tags(), $this->client->context->extra);
            }

            if (!empty($new_extras)) {
                $data['extra'] = array_merge($new_extras, $this->client->tags, $this->client->context->tags);
            }

            if (!empty($traces)) {
                $data['sentry.interfaces.Stacktrace'] = [
                    'frames' => Raven_Stacktrace::get_stack_info($traces),
                ];
            }

            $this->client->capture($data, false);
        }
    }
}