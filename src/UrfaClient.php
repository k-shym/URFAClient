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

    /** @var UrfaConnection $connection */
    private $connection;

    /** @var UrfaClientAbstract $api */
    private $api;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CacheItemPoolInterface $cache */
    private $cache;

    /** @var UrfaConfig $config */
    private $config;

    /**
     * UrfaClient constructor.
     * @param array $data
     * @param LoggerInterface|null $logger
     * @param CacheItemPoolInterface|null $cache
     */
    public function __construct(array $data = [], LoggerInterface $logger = null, CacheItemPoolInterface $cache = null)
    {
        //TODO Вынести подключение и настройку логера
        //        if ($logger === null) {
        //            $logger = new \Monolog\LoggerWrapper('UrfaClient');
        //        }
        // $logger->pushHandler(new StreamHandler('/var/www/telecom/UrfaClient/your.log', \Monolog\LoggerWrapper::DEBUG));

        $this->setLogger($logger);

        $this->setCache($cache);

        $this->setConfig($data);

    }

    /**
     * @param array|null $data
     * @return UrfaClientAbstract
     * @throws Exception\UrfaClientException
     */
    public function getApi($data = null)
    {
        $this->getConfig()->update($data);

        $this->api = new UrfaClientApi($this->getConnection()->connect(), $this->getCache());

        return $this->getConfig()->log ?
            new UrfaClientCollector($this->api, $this->getLogger()) :
            $this->api;
    }

    /**
     * @return UrfaConnection
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = new UrfaConnection($this->config);
        }

        return $this->connection;
    }


    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param mixed $logger
     * @return UrfaClient
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param mixed $cache
     * @return UrfaClient
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }


    /**
     * @return UrfaConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     * @return UrfaClient
     */
    public function setConfig($config)
    {
        if ($this->config === null) {
            $this->config = new UrfaConfig($config);
        }

        $this->getConfig()->update($config);

        return $this;
    }

}
