<?php

namespace laxity7\sentry;

use yii\base\Component;
use yii\base\ErrorException;

/**
 * Class ErrorHandler
 */
class ErrorHandler extends Component
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
     * @var callable|array|null User context for Raven_Client.
     * Callable must return array and its signature must be as follows:
     *
     * ```php
     * function ($client)
     * ```
     */
    public $userContext = null;

    /**
     * @var \Raven_Client
     */
    protected $client;

    /**
     * @var \Raven_ErrorHandler
     */
    protected $ravenErrorHandler;

    /**
     * @var callable|null
     */
    protected $oldExceptionHandler;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->client = new \Raven_Client($this->dsn, $this->clientOptions);

        $this->setUserContext();
        $this->ravenErrorHandler = new \Raven_ErrorHandler($this->client);
        $this->ravenErrorHandler->registerErrorHandler(true);
        // shutdown function not working in yii2 yet: https://github.com/yiisoft/yii2/issues/6637
        //$this->ravenErrorHandler->registerShutdownFunction();
        $this->oldExceptionHandler = set_exception_handler([$this, 'handleYiiExceptions']);
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

    /**
     * @param \Exception $e
     */
    public function handleYiiExceptions($e)
    {
        restore_exception_handler();

        if ($this->canLogException($e)) {
            $e->event_id = $this->client->getIdent($this->client->captureException($e));
        }

        if ($this->oldExceptionHandler) {
            call_user_func($this->oldExceptionHandler, $e);
        }
    }

    /**
     * Filter exception and its previous exceptions for yii\base\ErrorException
     * Raven expects normal stacktrace, but yii\base\ErrorException may have xdebug_get_function_stack
     *
     * @param \Exception $e
     *
     * @return bool
     */
    public function canLogException(&$e)
    {
        if (function_exists('xdebug_get_function_stack')) {
            if ($e instanceof ErrorException) {
                return false;
            }

            $selectedException = $e;
            while ($nestedException = $selectedException->getPrevious()) {
                if ($nestedException instanceof ErrorException) {
                    $ref = new \ReflectionProperty('Exception', 'previous');
                    $ref->setAccessible(true);
                    $ref->setValue($selectedException, null);

                    return true;
                }
                $selectedException = $selectedException->getPrevious();
            }
        }

        return true;
    }

    /**
     * @return \Raven_Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
