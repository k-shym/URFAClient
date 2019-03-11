<?php

namespace UrfaClient\Log;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface
{

    /**
     * @var LoggerInterface
     */
    private $loggerInstance;

    /**
     * Logger constructor.
     * @param object|callable $logger
     */
    public function __construct($logger)
    {
        if (is_object($logger) && $logger instanceof LoggerInterface) {
            $this->loggerInstance = $logger;
        } else {
            throw new InvalidArgumentException('Invalid wrapped logger');
        }
    }

    /**
     * Метод записи вызова метода
     *
     * @param string $name Имя метода
     * @param mixed $params Переданные параметры метода
     * @param mixed $result Результат работы метода
     * @param float $time Время работы метода
     * @param string $error Сообщение ошибки
     */
    public function method(string $name, $params = null, $result = null, $time = 0, $error = '')
    {
        $params = trim(preg_replace('/\s+/', ' ', print_r($params, true)));
        $result = trim(preg_replace('/\s+/', ' ', print_r($result, true)));
        $time = round($time, 3);

        if ($error) {
            $this->error("$name( $params ): $error");
        } else {
            $this->info("$name( $params ) -> $result {$time}ms");
        }
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {

        if ($this->loggerInstance !== null) {
            $this->loggerInstance->log($level, $message, $context);
        }
    }
}
