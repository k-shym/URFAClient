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
    const VERSION = '1.3.1';

    const API_XML = 'api_53-003.xml';

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
        spl_autoload_register(array('URFAClient', 'autoload'));
    }

    /**
     * Метод инициализации
     *
     * @param array $data Массив с параметрами
     *
     * @return URFAClient_API
     * @throws Exception
     */
    public static function init(array $data)
    {
        $data = array_merge([
            'login'    => 'init',
            'password' => 'init',
            'address'  => 'localhost',
            'port'     => 11758,
            'timeout'  => 30,
            'protocol' => 'ssl',
            'admin'    => true,
            'api'      => __DIR__ . '/../xml/' . self::API_XML,
            'log'      => false,
        ], $data);

        $api = new URFAClient_API($data['api'], new URFAClient_Connection($data));

        return ($data['log']) ? new URFAClient_Collector($api) : $api;
    }

    /**
     * Лог выполненых запросов
     *
     * @return array
     */
    public static function traceLog()
    {
        return URFAClient_Log::instance()->extract_trace_log();
    }

    /**
     * Последняя ошибка
     *
     * @return string
     */
    public static function lastError()
    {
        return URFAClient_Log::instance()->get_last_error();
    }
}
