<?php

include_once __DIR__ . '/../init.php';

/**
 * Базовый класс для тестов URFAClient
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
abstract class URFAClientBaseTest extends PHPUnit_Framework_TestCase {

    /**
     * @var string
     */
    private static $_prefix;

    /**
     * @return string Уникальное слово для тестов
     */
    protected static function prefix()
    {
        if (is_null(self::$_prefix)) self::$_prefix = 'test' . date('YmdHis');
        return self::$_prefix;
    }

    /**
     * @var array
     */
    protected $_config = array();

    /**
     * @var URFAClient_API
     */
    protected $_api;

    /**
     * Создаем соединение для тестов
     */
    protected function setUp()
    {
        $this->_api = URFAClient::init($this->_config);
    }

    /**
     * Делаем финальные проверки и закрываем соединение
     */
    protected function tearDown()
    {
        $this->assertTrue(is_array(URFAClient::trace_log()));
        $this->assertTrue(is_string(URFAClient::last_error()));

        unset($this->_api);
    }
}
