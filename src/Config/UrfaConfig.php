<?php

namespace UrfaClient\Config;

/**
 * Class UrfaConfig
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 * @package UrfaClient\UrfaConfig
 */
final class UrfaConfig
{
    public const PROTOCOL_ALLOWED = ['auto', 'tls', 'ssl'];

    public const API_XML = 'api_53-005.xml';

    /**
     * @var string
     */
    private $login = 'init';
    /**
     * @var string
     */
    private $password = 'init';
    /**
     * @var string
     */
    private $host = 'localhost';
    /**
     * @var int
     */
    private $port = 11758;
    /**
     * @var int
     */
    private $timeout = 30;
    /**
     * @var string
     */
    private $protocol = 'auto';

    /**
     * @var string|null
     */
    private $session;

    /**
     * Выполнять от имени админа
     * @var bool
     */
    private $admin = false;

    /**
     * Кеширование результатов
     * @var bool
     */
    private $cache = false;

    /**
     * Время кеширование результатов (сек)
     * @var int
     */
    private $cacheTime = 60;


    /**
     * Путь до xml файла с функциями
     * @var string
     */
    private $api = __DIR__.'/../../xml/'.self::API_XML;

    /**
     * Логирование ошибок
     * @var bool
     */
    private $log = false;


    public function __construct(array $params = [])
    {
        $this->updateOptions($params);
    }

    /**
     * @param array $params
     * @return UrfaConfig
     */
    public function updateOptions(array $params): UrfaConfig
    {
        foreach ($params as $property => $value) {
            [$getterFunction, $setterFunction] = $this->takeGetterAndSetterName($property);

            if (method_exists(__CLASS__, $setterFunction)) {
                $this->{$setterFunction}($value);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     * @return UrfaConfig
     */
    public function setLogin(string $login): UrfaConfig
    {
        $this->login = $login;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return UrfaConfig
     */
    public function setPassword(string $password): UrfaConfig
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return UrfaConfig
     */
    public function setHost(string $host): UrfaConfig
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return UrfaConfig
     */
    public function setPort(int $port): UrfaConfig
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return UrfaConfig
     */
    public function setTimeout(int $timeout): UrfaConfig
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     * @return UrfaConfig
     */
    public function setProtocol(string $protocol): UrfaConfig
    {
        $allowed = self::PROTOCOL_ALLOWED;
        if (\in_array($protocol, $allowed, true)) {
            $this->protocol = $protocol;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSession(): ?string
    {
        return $this->session;
    }

    /**
     * @param string|null $session
     * @return UrfaConfig
     */
    public function setSession(?string $session): UrfaConfig
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * @param bool $admin
     * @return UrfaConfig
     */
    public function setAdmin(bool $admin): UrfaConfig
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * @return bool
     */
    public function useCache(): bool
    {
        return $this->cache;
    }

    /**
     * @param bool $cache
     * @return UrfaConfig
     */
    public function setCache(bool $cache): UrfaConfig
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return int
     */
    public function getCacheTime(): int
    {
        return $this->cacheTime;
    }

    /**
     * @param int $cacheTime
     * @return UrfaConfig
     */
    public function setCacheTime(int $cacheTime): UrfaConfig
    {
        $this->cacheTime = $cacheTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getApi(): string
    {
        return $this->api;
    }

    /**
     * @param string $api
     * @return UrfaConfig
     */
    public function setApi(string $api): UrfaConfig
    {
        $this->api = $api;

        return $this;
    }

    /**
     * @return bool
     */
    public function useLog(): bool
    {
        return $this->log;
    }

    /**
     * @param bool $log
     * @return UrfaConfig
     */
    public function setLog(bool $log): UrfaConfig
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @param string $property
     * @return array
     */
    private function takeGetterAndSetterName(string $property): array
    {
        $prefix = 'get';
        switch ($property) {
            case 'cache':
            case 'log':
                $prefix = 'use';
                break;
            case 'admin':
                $prefix = 'is';
                break;
        }

        return [$prefix.ucfirst($property), 'set'.ucfirst($property)];
    }
}
