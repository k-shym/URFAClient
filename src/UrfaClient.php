<?php

namespace UrfaClient;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use UrfaClient\Client\UrfaClientAbstract;
use UrfaClient\Client\UrfaClientApi;
use UrfaClient\Common\UrfaConnection;
use UrfaClient\Config\UrfaConfig;
use UrfaClient\Exception\UrfaClientException;

/**
 * @license GNU General Public License v3.0
 * @author Konstantin Shum <k.shym@ya.ru>
 *
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 */
class UrfaClient extends UrfaClientAbstract
{
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
        $this
            ->setOptions($options)
            ->setLogger($logger)
            ->setCache($cache)
        ;
    }

    /**
     * @param array $options
     * @return UrfaClientAbstract
     * @throws Exception\UrfaClientException
     */
    public function getApi(array $options = []): UrfaClientAbstract
    {
        $this->setOptions($options);
        $this->api = new UrfaClientApi($this->getConnection()->connect());

        return $this;
    }

    public function createClient(array $options = []): UrfaClientAbstract
    {
        $config = clone $this->config;
        $config->updateOptions($options);

        return new self($config, $this->logger, $this->cache);
    }

    /**
     * @param string $name
     * @param array $args
     * @return bool|mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __call(string $name, array $args)
    {
        try {
            $ts = microtime(true);
            if (!$this->api) {
                $this->api = new UrfaClientApi($this->getConnection());
            }

            if ($this->getCache() && $this->getConfig()->useCache()) {
                $cacheKey = $name.'_'.sha1($this->getConfig()->getSession().serialize($args));

                $cacheCount = $this->getCache()->getItem($cacheKey);
                if (!$cacheCount->isHit()) {
                    if ($this->getConfig()->getCacheTime()) {
                        $cacheCount->expiresAfter($this->getConfig()->getCacheTime());
                    }
                    $data = call_user_func_array([$this->api, $name], $args);
                    $cacheCount->set($data);
                    $this->getCache()->save($cacheCount);
                }
                $result = $cacheCount->get();
            } else {
                $result = call_user_func_array([$this->api, $name], $args);
            }

            $te = microtime(true);
            $this->log($name, $args ? $args[0] : [], $result, $te - $ts);

            return $result;
        } catch (UrfaClientException $e) {
            if (!$this->getLogger() || !$this->getConfig()->useLog()) {
                throw $e;
            } else {
                $this->log($name, $args ? $args[0] : [], null, 0, $e->getMessage());

                return false;
            }
        }
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
    public function setOptions(array $options): UrfaClient
    {
        if ($this->config === null) {
            $this->config = new UrfaConfig($options);
        }

        $this->getConfig()->updateOptions($options);

        return $this;
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
    private function log(string $name, $params = null, $result = null, $time = 0, $error = ''): void
    {
        if ($this->getLogger() && $this->getConfig()->useLog()) {

            $time = round($time, 3);

            if ($error) {
                $this->getLogger()->error("$name: $error", ['params' => $params]);
            } else {
                $this->getLogger()->debug("$name -> {$time} ms", ['params' => $params, 'result' => $result]);
            }
        }
    }
}
