<?php

namespace UrfaClient\Client;

use Psr\Log\LoggerInterface;
use UrfaClient\Exception\UrfaClientException;
use UrfaClient\Log\LoggerWrapper;

/**
 * Сборщик информации для класса UrfaClient_API
 *
 * @license https://github.com/k-shym/UrfaClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 *
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 */
final class UrfaClientCollector extends UrfaClientAbstract
{

    /**
     * @var UrfaClientApi $api
     */
    private $api;

    /**
     * UrfaClientCollector constructor.
     * @param UrfaClientApi $api
     * @param LoggerInterface $logger
     */
    public function __construct(UrfaClientApi $api, LoggerInterface $logger)
    {
        $this->api = $api;
        $this->setLogger($logger);
    }

    /**
     * @var LoggerWrapper|null
     */
    private $logger;

    /**
     * Устанавливает логгер приложения
     *
     * @param LoggerInterface $value Инстанс логгера
     */
    public function setLogger($value)
    {
        $this->logger = new LoggerWrapper($value);
    }

    /**
     * Магический метод для сборки информации о вызваных методах API
     *
     * @param   string $name Имя метода
     * @param   array $args Аргументы
     * @return  bool
     */
    public function __call(string $name, array $args)
    {

        try {
            $ts = microtime(true);
            $result = call_user_func_array([$this->api, $name], $args);
            $te = microtime(true);
            $this->log($name, $args ? $args[0] : [], $result, $te - $ts);

            return $result;
        } catch (UrfaClientException $e) {
            $this->log($name, $args ? $args[0] : [], null, 0, $e->getMessage());

            return false;
        }
    }

    /**
     * Логирование информации
     *
     * @param string $name Имя метода
     * @param mixed $params Переданные параметры метода
     * @param mixed $result Результат работы метода
     * @param float $time Время работы метода
     * @param string $error Сообщение ошибки
     */
    public function log(string $name, $params = null, $result = null, $time = 0, $error = '')
    {
        $params = trim(preg_replace('/\s+/', ' ', print_r($params, true)));
        $result = trim(preg_replace('/\s+/', ' ', print_r($result, true)));
        $time = round($time, 3);

        if ($error) {
            $this->logger->error("$name( $params ): $error");
        } else {
            $this->logger->info("$name( $params ) -> $result {$time}ms");
        }
    }
}
