<?php

namespace UrfaClient;

use UrfaClient\Client\UrfaClientAbstract;
use UrfaClient\Client\UrfaClientApi;
use UrfaClient\Client\UrfaClientCollector;
use UrfaClient\Common\Connection;

/**
 * Главный класс
 *
 * @license https://github.com/k-shym/UrfaClient/blob/master/LICENSE.md
 * @author Konstantin Shum <k.shym@ya.ru>
 *
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 */
class UrfaClient
{

    public const VERSION = '2.0.0';

    public const API_XML = 'api_53-005.xml';

    /**
     * @param array $data
     * @return UrfaClientAbstract
     * @throws \UrfaClient\Exception\UrfaClientException
     */
    public static function init(array $data)
    {
        $data = array_merge([
            'login' => 'init',
            'password' => 'init',
            'address' => 'localhost',
            'port' => 11758,
            'timeout' => 30,
            'protocol' => 'auto',
            'admin' => true,
            'api' => __DIR__.'/../xml/'.self::API_XML,
            'log' => true,
        ], $data);

        $api = new UrfaClientApi($data['api'], new Connection($data));


        //TODO Вынести подключение и настройку логера
        $logger = new \Monolog\Logger('UrfaClient');

        // $logger->pushHandler(new StreamHandler('/var/www/telecom/UrfaClient/your.log', \Monolog\Logger::DEBUG));


        return $data['log'] ? new UrfaClientCollector($api, $logger) : $api;
    }
}
