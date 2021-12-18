<?php

namespace Tests;

use URFAClient\URFAClient;
use URFAClient\API;

/**
 * Базовый класс для тестов URFAClient
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
abstract class URFAClientBaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Уникальная строка
     *
     * @var string
     */
    private static $prefix;

    /**
     * Уникальная строка для тестов
     *
     * @return string
     */
    protected static function prefix()
    {
        if (is_null(self::$prefix)) {
            self::$prefix = date('YmdHis');
        }
        return self::$prefix;
    }

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var API
     */
    protected $api;

    /**
     * Создаем соединение для тестов
     *
     * @return void
     * @throws \Exception
     */
    protected function setUp()
    {
        $this->api = URFAClient::init($this->config);
    }

    /**
     * @return void
     */
    public function testLiburfaList()
    {
        $this->assertTrue((bool) count($this->api->rpcf_liburfa_list()));
    }

    /**
     * Делаем финальные проверки и закрываем соединение
     */
    protected function tearDown()
    {
        unset($this->api);
    }
}
