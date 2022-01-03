<?php

/**
 * Главный класс
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
abstract class URFAClient {

    const VERSION = '1.3.5';

    const API_XML = 'api_53-003.xml';

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
     * @param   array $data
     * @return  URFAClient_Function
     * @throws  Exception
     */
    public static function init(array $data)
    {
        $data = array_merge(array(
            'login'    => 'init',
            'password' => 'init',
            'address'  => 'localhost',
            'port'     => 11758,
            'timeout'  => 30,
            'protocol' => 'ssl',
            'admin'    => TRUE,
            'api'      => __DIR__ . '/../xml/' . self::API_XML,
            'log'      => FALSE,
        ), $data);

        $api = new URFAClient_API($data['api'], new URFAClient_Connection($data));

        return ($data['log']) ? new URFAClient_Collector($api) : $api;
    }

    /**
     * Лог выполненых запросов
     *
     * @return array
     */
    public static function trace_log() {
        return URFAClient_Log::instance()->extract_trace_log();
    }

    /**
     * Последняя ошибка
     *
     * @return string
     */
    public static function last_error() {
        return URFAClient_Log::instance()->get_last_error();
    }
}
