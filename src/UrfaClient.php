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
     * @param array $options
     * @param LoggerInterface|null $logger
     * @param CacheItemPoolInterface|null $cache
     */
    public function __construct(array $options = [], LoggerInterface $logger = null, CacheItemPoolInterface $cache = null)
    {
        $this->setOptions($options);

        $this->setLogger($logger);

        $this->setCache($cache);
    }

    /**
     * @param array|null $options
     * @return UrfaClientAbstract
     * @throws Exception\UrfaClientException
     */
    public function getApi($options = null): UrfaClientAbstract
    {
        $this->setOptions($options);

        $this->api = new UrfaClientApi($this->getConnection()->connect(), $this->getCache());

        return $this->getConfig()->isLog() ?
            new UrfaClientCollector($this->api, $this->getLogger()) :
            $this->api;
    }

    /**
     * @return UrfaConnection
     */
    public function getConnection(): UrfaConnection
    {
        if ($this->connection === null) {
            $this->connection = new UrfaConnection($this->getConfig());
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
     * @param LoggerInterface $logger
     * @return UrfaClient
     */
    public function setLogger(?LoggerInterface $logger): UrfaClient
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
     * @param CacheItemPoolInterface $cache
     * @return UrfaClient
     */
    public function setCache(?CacheItemPoolInterface $cache): UrfaClient
    {
        $this->cache = $cache;

        return $this;
    }


    /**
     * @return UrfaConfig
     */
    public function getConfig(): UrfaConfig
    {
        return $this->config;
    }

    /**
     * @param array $options
     * @return UrfaClient
     */
    public function setOptions($options): UrfaClient
    {
        if ($this->config === null) {
            $this->config = new UrfaConfig($options);
        }

        $this->getConfig()->updateOptions($options);

        return $this;
    }
}
