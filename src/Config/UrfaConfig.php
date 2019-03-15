<?php

namespace UrfaClient\Config;


use UrfaClient\UrfaClient;

/**
 * Class UrfaConfig
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 * @package UrfaClient\UrfaConfig
 */
class UrfaConfig
{
    /**
     * @var string
     */
    public $login = 'init';
    /**
     * @var string
     */
    public $password = 'init';
    /**
     * @var string
     */
    public $address = 'localhost';
    /**
     * @var int
     */
    public $port = 11758;
    /**
     * @var int
     */
    public $timeout = 30;
    /**
     * @var string
     */
    public $protocol = 'auto';

    /**
     * @var string|null
     */
    public $session = null;

    /**
     * Выполнять от имени админа
     * @var bool
     */
    public $admin = false;

    /**
     * Кеширование результатов
     * @var bool
     */
    public $cache = false;

    /**
     * Время кеширование результатов (сек)
     * @var bool
     */
    public $cache_time = 60;


    /**
     * Путь до xml файла с функциями
     * @var string
     */
    public $api = __DIR__.'/../../xml/'.UrfaClient::API_XML;

    /**
     * Логирование ошибок
     * @var bool
     */
    public $log = false;

    public function __construct(array $params = [])
    {
        $this->update($params);
    }

    /**
     * @param array $params
     * @return UrfaConfig
     */
    public function update(array $params): UrfaConfig
    {
        foreach ($params as $key => $param) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $param;
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
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return UrfaConfig
     */
    public function setAddress(string $address): UrfaConfig
    {
        $this->address = $address;

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
        $this->protocol = $protocol;

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
    public function isCache(): bool
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
     * @return bool
     */
    public function isCacheTime(): bool
    {
        return $this->cache_time;
    }

    /**
     * @param bool $cache_time
     * @return UrfaConfig
     */
    public function setCacheTime(bool $cache_time): UrfaConfig
    {
        $this->cache_time = $cache_time;

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
    public function isLog(): bool
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
    
    
}