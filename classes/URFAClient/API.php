<?php

/**
 * Объект предоставляет обращение к функциям из api.xml
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
class URFAClient_API extends URFAClient_Function {

    /**
     * @var URFAClient_Connection
     */
    protected $_connection;

    /**
     * @var SimpleXMLElement
     */
    protected $_api;

    /**
     * @var array
     */
    protected $_data_input = array();

    /**
     * @var array
     */
    protected $_data_output = array();

    /**
     * Конструктор класса
     *
     * @param  string                $api            Путь до файла api
     * @param  URFAClient_Connection $connection     Объект соединения с ядром
     * @throws Exception
     */
    public function __construct($api, URFAClient_Connection $connection = NULL)
    {
        $this->_connection = $connection;

        if ( ! file_exists($api))
        {
            throw new Exception("File $api not found");
        }

        $this->_api = simplexml_load_file($api);

        if ( ! $this->_api->xpath("/urfa/function[contains(@name, 'ipv6')]")) $connection->ipv6 = FALSE;
    }

    /**
     * Магический метод для вызова функций из api.xml
     *
     * @param   string  $name
     * @param   array   $args
     * @return  array
     * @throws  Exception
     */
    public function __call($name, $args)
    {
        if ( ! $this->_connection) throw new Exception("No object URFAClient_Connection");

        $this->_data_input = $this->_data_output = array();

        $method = FALSE;
        foreach ($this->_api->function as $function)
        {
            if ((string) $function->attributes()->{'name'} === $name)
            {
                $method = $function;
                break;
            }
        }

        if ( ! $method) throw new Exception("Function $name not found");

        $args = (isset($args[0]) AND is_array($args[0])) ? (array) $args[0] : array();

        $this->_process_data_input($method->input, $args);

        $code = (string) $method->attributes()->{'id'};
        $code = (substr($code, 0, 1) === '-') ? -1 * hexdec(substr($code, 1)) : hexdec($code);

        if ( ! $this->_connection->call($code)) throw new Exception("Error calling function $name");

        $packet = $this->_connection->packet();
        foreach ($this->_data_input as $v)
        {
            switch ($v['type'])
            {
                case 'integer': $packet->set_data_int($v['value']); break;
                case 'long': $packet->set_data_long($v['value']); break;
                case 'double': $packet->set_data_double($v['value']); break;
                case 'ip_address': $packet->set_data_ip($v['value']); break;
                case 'string': $packet->set_data_string($v['value']); break;
                default: throw new Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
            }
        }
        if ($this->_data_input) $this->_connection->write($packet);

        return $this->_process_data_output($method->output, $this->_connection->result());
    }

    /**
     * Рекурсивная функция обработки входных параметров api.xml
     *
     * @param SimpleXMLElement  $input  Элемент дерева api.xml
     * @param array             $args   Переданные аргументы метода
     * @throws Exception
     */
    protected function _process_data_input(SimpleXMLElement $input, Array $args)
    {
        foreach ($input->children() as $node)
        {
            switch ($node->getName())
            {
                case 'integer':
                case 'long':
                case 'double':
                case 'ip_address':
                case 'string': $this->_process_data_input_scalar($node, $args, $node->getName()); break;

                case 'error': $this->_process_data_error($node); break;

                case 'if':
                    $attr = $node->attributes();
                    $variable = (string) $attr->{'variable'};

                    foreach ($this->_data_input as $v)
                        if ($v['name'] === $variable)
                        {
                            $variable = $v;
                            break;
                        }

                    if ( ! is_array($variable))
                        throw new Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');

                    switch ($variable['type'])
                    {
                        case 'integer': $value = (int) $attr->{'value'}; break;
                        case 'double': $value = (float) $attr->{'value'}; break;
                        default: $value = (string) $attr->{'value'};
                    }

                    switch ((string) $attr->{'condition'})
                    {
                        case 'eq':
                            if ($variable['value'] === $value) $this->_process_data_input($node, $args);
                            break;

                        case 'ne':
                            if ($variable['value'] !== $value) $this->_process_data_input($node, $args);
                            break;
                    }
                    break;

                case 'for':
                    $sibling = $node->xpath('preceding-sibling::integer[1]');

                    if ( ! isset($sibling[0])) throw new Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');

                    $name = (string) $sibling[0]->attributes()->{'name'};

                    if ( ! isset($args[$name])) break;

                    if ( ! is_array($args[$name])) throw new Exception("$name can only be an array");

                    foreach ($args[$name] as $v)
                    {
                        if ( ! is_array($v))
                            throw new Exception('To tag "for" an array must be two-dimensional');

                        $this->_process_data_input($node, $v);
                    }
                    break;
            }
        }
    }

    /**
     * Обработка скалярных типов данных
     *
     * @param SimpleXMLElement  $node   Элемент дерева api.xml
     * @param array             $args   Переданные аргументы метода
     * @param string            $type   Тип данных (integer|long|double|ip_address|string)
     * @throws Exception
     */
    protected function _process_data_input_scalar(SimpleXMLElement $node, Array $args, $type)
    {
        $attr = $node->attributes();

        $name = (string) $attr->{'name'};

        $default = (isset($attr->{'default'})) ? (string) $attr->{'default'} : NULL;
        $default = ($default === 'now()') ? time() : $default;
        $default = ($default === 'max_time()') ? 2000000000 : $default;

        $sibling = $node->xpath('following-sibling::*[1]');
        $sibling = (isset($sibling[0])) ? $sibling[0] : FALSE;

        if ($sibling AND $sibling->getName() === 'for')
        {
            $this->_data_input[] = array(
                'name'  => $name,
                'value' => (isset($args[$name]) AND is_array($args[$name])) ? count($args[$name]) : 0,
                'type'  => $type,
            );

            return;
        }

        if (array_key_exists($name, $args))
        {
            $valid = TRUE;

            switch ($type)
            {
                case 'integer':
                    $args[$name] = (int) $args[$name];
                    break;
                case 'double':
                    $args[$name] = (float) $args[$name];
                    break;
                case 'ip_address':
                    $valid = (bool) filter_var($args[$name], FILTER_VALIDATE_IP);
                    break;
                case 'long':
                case 'string':
                    $args[$name] = (string) $args[$name];
                    break;
                default: $valid = FALSE;
            }

            if ($valid) $value = $args[$name];
            else throw new Exception("$name can only be a $type");
        }
        else
        {
            if ( ! is_null($default)) $value = $default;
            else throw new Exception("Required parameter $name ($type)");
        }

        $this->_data_input[] = array(
            'name'  => $name,
            'value' => $value,
            'type'  => $type,
        );
    }

    /**
     * Рекурсивная функция обработки выходных параметров api.xml
     *
     * @param SimpleXMLElement  $output   Элемент дерева api.xml
     * @param URFAClient_Packet $packet   Пакет с бинарными данными
     * @return array
     * @throws Exception
     */
    protected function _process_data_output(SimpleXMLElement $output, URFAClient_Packet $packet)
    {
        $result = array();

        foreach ($output->children() as $node)
        {
            $attr = $node->attributes();

            switch ($node->getName())
            {
                case 'integer': $result[(string) $attr->{'name'}] = $packet->get_data_int(); break;
                case 'long': $result[(string) $attr->{'name'}] = $packet->get_data_long(); break;
                case 'double': $result[(string) $attr->{'name'}] = $packet->get_data_double(); break;
                case 'ip_address': $result[(string) $attr->{'name'}] = $packet->get_data_ip(); break;
                case 'string': $result[(string) $attr->{'name'}] = $packet->get_data_string(); break;

                case 'error': $this->_process_data_error($node); break;

                case 'set':
                    $dst = (string) $node->attributes()->{'dst'};
                    $src = (string) $node->attributes()->{'src'};
                    $value = (string) $node->attributes()->{'value'};

                    if ( ! $dst) break;

                    if ($src AND isset($result[$src])) $value = $result[$src];

                    $result[$dst] = $value;
                    break;

                case 'if':
                    $variable = (string) $attr->{'variable'};

                    $result_value = FALSE;
                    foreach ($result as $k => $v)
                        if ($k === $variable)
                        {
                            $result_value = $v;
                            break;
                        }

                    if ($result_value === FALSE)
                        foreach ($this->_data_input as $v)
                            if ($v['name'] === $variable)
                            {
                                $result_value = $v['value'];
                                break;
                            }

                    if ($result_value === FALSE) break;

                    switch (gettype($result_value))
                    {
                        case 'integer': $value = (int) $attr->{'value'}; break;
                        case 'double': $value = (float) $attr->{'value'}; break;
                        case 'string': $value = (string) $attr->{'value'}; break;
                        default: throw new Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                    }

                    switch ((string) $attr->{'condition'})
                    {
                        case 'eq':
                            if ($result_value === $value) $result = array_merge($result, $this->_process_data_output($node, $packet));
                            break;

                        case 'ne':
                            if ($result_value !== $value) $result = array_merge($result, $this->_process_data_output($node, $packet));
                            break;
                    }
                    break;

                case 'for':
                    $sibling = $node->xpath('preceding-sibling::integer[1]');

                    if ( ! $sibling) $sibling = $node->xpath('parent::*[1]/preceding-sibling::integer[1]');

                    if ( ! isset($sibling[0])) throw new Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');

                    $name = (string) $sibling[0]->attributes()->{'name'};

                    if (isset($result[$name]) AND is_array($result[$name])) break;

                    $count = (int) ((isset($result[$name])) ? $result[$name] : $this->_data_output[$name]);
                    $array = array();
                    for ($i=0; $i<$count; $i++) $array[] = $this->_process_data_output($node, $packet);

                    $result[$name] = $array;

                    break;
            }

            $this->_data_output += $result;
        }

        return $result;
    }

    /**
     * Метод обработки элементов error в api.xml
     *
     * @param SimpleXMLElement  $node
     * @throws Exception
     */
    protected function _process_data_error(SimpleXMLElement $node)
    {
        $attr = $node->attributes();

        $code = (isset($attr->{'code'})) ? "Code: {$attr->{'code'}}" : '';
        $comment = (isset($attr->{'comment'})) ? "Comment: {$attr->{'comment'}}" : '';
        $variable = (isset($attr->{'variable'})) ? "Variable: {$attr->{'variable'}}" : '';

        throw new Exception("XML Described error: $code $comment $variable");
    }
}