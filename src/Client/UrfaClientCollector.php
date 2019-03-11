<?php

namespace UrfaClient\Client;

use Psr\Log\LoggerInterface;
use UrfaClient\Exception\UrfaClientException;
use UrfaClient\Log\Logger;

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
     * @var Logger|null
     */
    private $logger;

    /**
     * Устанавливает логгер приложения
     *
     * @param LoggerInterface $value Инстанс логгера
     */
    public function setLogger($value)
    {
        $this->logger = new Logger($value);
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
            $this->logger->method($name, $args ? $args[0] : [], $result, $te - $ts);

            return $result;
        } catch (UrfaClientException $e) {
            $this->logger->method($name, $args ? $args[0] : [], null, 0, $e->getMessage());

            return false;
        }
    }
}
