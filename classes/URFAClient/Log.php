<?php

/**
 * Журнал с собранными данными
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
final class URFAClient_Log {

    protected static $_instance = NULL;

    /**
     * Точка достпуа к объекту (singleton)
     *
     * @return URFAClient_Log
     */
	public static function instance()
    {
		if (is_null(self::$_instance))
			self::$_instance = new URFAClient_Log();
		return self::$_instance;
	}

    private
        $_trace_log = array(),
        $_trace_lenght = 100,
        $_last_lrror = '';

    /**
     * Конструктор журнала
     */
    private function __construct() {}

    /**
     * Получить данные журнала
     *
     * @return Array
     */
    public function get_traceLog()
    {
        return $this->_trace_log;
    }

    /**
     * Извлечь данные журнала
     *
     * @return Array
     */
    public function extract_trace_log()
    {
        $trace = $this->_trace_log;
        $this->clear();
        return $trace;
    }

    /**
     * Получить последнюю ошибку
     *
     * @return String
     */
    public function get_last_error()
    {
        return $this->_last_lrror;
    }

    /**
     * Метод записи вызова метода
     *
     * @param String $name Имя метода
     * @param Mixed $params Переданные параметры метода
     * @param Mixed $result Результат работы метода
     * @param String $error Сообщение ошибки
     */
    public function method($name, $params = NULL, $result = NULL, $time = 0, $error = '')
    {
        $params = trim(preg_replace('/\s+/', ' ', print_r($params, TRUE)));
        $result = trim(preg_replace('/\s+/', ' ', print_r($result, TRUE)));
        $time = round($time, 3);

        if ($error) $this->error("$name( $params ): $error");
        else $this->info("$name( $params ) -> $result {$time}ms");
    }

    /**
     * Метод описания ошибочных сообщений
     *
     * @param String $msg Сообщение
     */
    public function error($msg) {
        $this->_last_lrror = $msg;
        $this->write("ERROR: $msg");
    }

    /**
     * Метод описания информационных сообщений
     *
     * @param String $msg Сообщение
     */
    public function info($msg)
    {
        $this->write("INFO: $msg");
    }

    /**
     * Метод записи в журнал
     *
     * @param String $string Строка для записи
     * @return void
     */
    private function write($string)
    {
        if (count($this->_trace_log) >= $this->_trace_lenght) array_shift($this->_trace_log);
        $this->_trace_log[] = date('Y.m.d H:i:s') . " $string";
    }

    /**
     * Удаляем собранные данные
     *
     * @return void
     */
    public function clear()
    {
        $this->_trace_log = array();
    }
}
