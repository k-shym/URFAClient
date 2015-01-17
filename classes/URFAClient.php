<?php

/**
 * Главный класс
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
abstract class URFAClient {

    const VERSION = '1.0.8';

    /**
     * Автозагрузка класса
     *
     * @param string $class		Имя класса
     */
    public static function autoload($class)
    {
        if (strpos($class, 'URFAClient_') !== 0) return;

        $path = __DIR__ . '/' . str_replace('_', '/', $class) . '.php';

        if ( ! file_exists($path)) return;

        require $path;
    }

    /**
     * Включаем автозагрузку классов
     */
    public static function register_autoload()
    {
        spl_autoload_register(array('URFAClient', 'autoload'));
    }

    /**
     * Метод инициализации
     *
     * @param   Array $data
     * @return  URFAClient_API
     */
    public static function init(Array $data)
    {
        $data = array_merge(array(
            'login'    => 'init',
            'password' => 'init',
            'address'  => 'localhost',
            'port'     => 11758,
            'timeout'  => 30,
            'admin'    => TRUE,
            'api'      => __DIR__ . '/../api.xml',
            'log'      => FALSE,
        ), $data);

        $api = new URFAClient_API($data['api'], new URFAClient_Connection($data));

        return ($data['log']) ? new URFAClient_Collector($api) : $api;
    }

    /**
     * Лог выполненых запросов
     *
     * @return Array
     */
    public static function trace_log() {
        return URFAClient_Log::instance()->extract_trace_log();
    }

    /**
     * Последняя ошибка
     *
     * @return String
     */
    public static function last_error() {
        return URFAClient_Log::instance()->get_last_error();
    }
}
