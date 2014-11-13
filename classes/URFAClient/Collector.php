<?php

/**
 * Cборщик информации для класса API
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
final class URFAClient_Collector {

    /**
     * @var URFAClient_API  $api
     */
    private $_api;

    /**
     * Конструктор сборщика
     *
     * @param   URFAClient_API  $api
     */
    public function __construct(URFAClient_API $api) {
        $this->_api = $api;
    }

    /**
     * Магический метод для сборки информации о вызваных методах API
     *
     * @param   String    $name   Имя метода
     * @param   Array     $args   Аргументы
     */
    public function __call($name, Array $args) {
        try {
            $ts = microtime(TRUE);
            $result = call_user_func_array(array($this->_api, $name), $args);
            $te = microtime(TRUE);
            URFAClient_Log::instance()->method($name, ($args) ? $args[0] : array(), $result, $te - $ts);
            return $result;
        } catch (Exception $e) {
            URFAClient_Log::instance()->method($name, ($args) ? $args[0] : array(), NULL, 0, $e->getMessage());
            return FALSE;
        }
    }
}