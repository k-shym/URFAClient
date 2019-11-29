<?php

/**
 * Главный класс
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
abstract class URFAClient
{
    const VERSION = '1.4.0';

    const API_XML = 'api_53-005.xml';

    /**
     * Автозагрузка класса
     *
     * @param string $class Имя класса
     *
     * @return string
     */
    public static function autoload($class)
    {
        if (strpos($class, 'URFAClient_') !== 0) {
            return null;
        }

        $path = __DIR__ . '/' . str_replace('_', '/', $class) . '.php';

        if (!file_exists($path)) {
            return null;
        }

        include $path;
    }

    /**
     * Включаем автозагрузку классов
     *
     * @return void
     */
    public static function registerAutoload()
    {
        spl_autoload_register(['URFAClient', 'autoload']);
    }

    /**
     * Метод инициализации
     *
     * @param array $data Массив с параметрами
     *
     * @return URFAClient_API
     * @throws URFAClient_Exception
     */
    public static function init(array $data)
    {
        $data = array_merge([
            'login'    => 'init',
            'password' => 'init',
            'address'  => 'localhost',
            'port'     => 11758,
            'timeout'  => 30,
            'protocol' => 'auto',
            'admin'    => true,
            'api'      => __DIR__ . '/../xml/' . self::API_XML,
        ], $data);

        return new URFAClient_API($data['api'], new URFAClient_Connection($data));
    }
}
