<?php

namespace UrfaClient;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use UrfaClient\Client\UrfaClientAbstract;
use UrfaClient\Client\UrfaClientApi;
use UrfaClient\Client\UrfaClientCollector;
use UrfaClient\Common\UrfaConnection;
use UrfaClient\Config\UrfaConfig;

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
     * @param LoggerInterface|null $logger
     * @param CacheItemPoolInterface|null $cache
     * @return UrfaClientAbstract
     * @throws Exception\UrfaClientException
     */
    public function create(array $data, LoggerInterface $logger = null, CacheItemPoolInterface $cache = null)
    {

        $config = new UrfaConfig($data);
        $connection = new UrfaConnection($config);
        $api = new UrfaClientApi($connection->connect(), $cache);


        //TODO Вынести подключение и настройку логера
        if ($logger === null) {
            $logger = new \Monolog\Logger('UrfaClient');
        }

        // $logger->pushHandler(new StreamHandler('/var/www/telecom/UrfaClient/your.log', \Monolog\Logger::DEBUG));


        return $config->log ? new UrfaClientCollector($api, $logger) : $api;
    }
}
