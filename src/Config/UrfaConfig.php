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
}