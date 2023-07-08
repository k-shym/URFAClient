<?php

namespace URFAClient;

use ArrayObject;

/**
 * Объект предоставляет обращение к функциям из api.xml
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class API extends URFAFunction
{
    /**
     * Объект соединения
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Объект XML API файла
     *
     * @var \SimpleXMLElement
     */
    protected $api;

    /**
     * Подготовленные данные для отправки на сервер
     *
     * @var array
     */
    protected $data_input = [];

    /**
     * Подготовленные данные от сервера
     *
     * @var array
     */
    protected $data_output = [];

    /**
     * Данные из тегов set с атрибутом src
     *
     * @var array
     */
    protected $data_set_src = [];

    /**
     * Данные из тегов set с атрибутом value
     *
     * @var array
     */
    protected $data_set_value = [];

    /**
     * Конструктор класса
     *
     * @param string     $api        Путь до файла api
     * @param Connection $connection Объект соединения с ядром
     *
     * @throws URFAException
     */
    public function __construct($api, Connection $connection = null)
    {
        if (strpos($api, DIRECTORY_SEPARATOR) === false) {
            $api = __DIR__ . '/../xml/' . $api;
        }

        if (!file_exists($api)) {
            throw new URFAException("File $api not found");
        }

        $this->connection = $connection;
        $this->api = simplexml_load_file($api);

        if (!$this->api->xpath("/urfa/function[contains(@name, 'ipv6')]")) {
            $connection->ipv6 = false;
        }
    }

    /**
     * Магический метод для вызова функций из api.xml
     *
     * @param string $name Имя функции
     * @param array  $args Параметры функции
     *
     * @return ArrayObject
     * @throws URFAException
     */
    public function __call($name, $args)
    {
        if (!$this->connection) {
            throw new URFAException("No object URFAClient_Connection");
        }

        $method = false;
        foreach ($this->api->function as $function) {
            if ((string) $function->attributes()->{'name'} === $name) {
                $method = $function;
                break;
            }
        }

        if (!$method) {
            throw new URFAException("Function $name not found");
        }

        if (isset($args[0])) {
            if (is_string($args[0])) {
                $args = json_decode($args[0], JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY);
                $args = $args ?: [];
            } elseif (is_array($args[0])) {
                $args = $args[0];
            } else {
                $args = $args[0]->getArrayCopy();
            }
        } else {
            $args = [];
        }

        $this->cleanData()->processDataInput($method->input, $args);
        $code = (string) $method->attributes()->{'id'};
        $code = ($code[0] === '-') ? -1 * hexdec(substr($code, 1)) : hexdec($code);

        if (!$this->connection->call($code)) {
            throw new URFAException("Error calling function $name");
        }

        $packet = $this->connection->packet();
        foreach ($this->data_input as $v) {
            switch ($v['type']) {
                case 'integer':
                    $packet->setDataInt($v['value']);
                    break;
                case 'long':
                    $packet->setDataLong($v['value']);
                    break;
                case 'double':
                    $packet->setDataDouble($v['value']);
                    break;
                case 'ip_address':
                    $packet->setDataIp($v['value']);
                    break;
                case 'string':
                    $packet->setDataString($v['value']);
                    break;
                default:
                    throw new URFAException('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
            }
        }
        if ($this->data_input) {
            $this->connection->write($packet);
        }

        return $this->processDataOutput($method->output, $this->connection->result());
    }

    /**
     * Рекурсивная функция обработки входных параметров api.xml
     *
     * @param \SimpleXMLElement $input Элемент дерева api.xml
     * @param array             $args  Переданные аргументы метода
     *
     * @return void
     * @throws URFAException
     */
    protected function processDataInput(\SimpleXMLElement $input, array $args)
    {
        foreach ($input->children() as $node) {
            $attr = $node->attributes();
            switch ($node->getName()) {
                case 'integer':
                case 'long':
                case 'double':
                case 'ip_address':
                case 'string':
                    $this->processDataInputScalar($node, $args, $node->getName());
                    break;
                case 'error':
                    $this->processDataError($node);
                    // no break
                case 'if':
                    $variable = (string) $attr->{'variable'};
                    foreach ($this->data_input as $v) {
                        if ($v['name'] === $variable) {
                            $variable = $v;
                            break;
                        }
                    }

                    if (!is_array($variable) and isset($this->data_set_src[$variable])) {
                        foreach ($this->data_input as $v) {
                            if ($v['name'] === $this->data_set_src[$variable]) {
                                $variable = $v;
                                break;
                            }
                        }
                    }

                    if (!is_array($variable)) {
                        throw new URFAException('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                    }

                    switch ($variable['type']) {
                        case 'integer':
                            $value = (int) $attr->{'value'};
                            break;
                        case 'double':
                            $value = (float) $attr->{'value'};
                            break;
                        default:
                            $value = (string) $attr->{'value'};
                    }

                    switch ((string) $attr->{'condition'}) {
                        case 'eq':
                            if ($variable['value'] === $value) {
                                $this->processDataInput($node, $args);
                            }
                            break;
                        case 'ne':
                            if ($variable['value'] !== $value) {
                                $this->processDataInput($node, $args);
                            }
                            break;
                    }
                    break;

                case 'for':
                    $sibling = $node->xpath("preceding-sibling::*[name()='integer' or name()='long'][1]");

                    if (!isset($sibling[0])) {
                        throw new URFAException('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                    }

                    $name = (string) $sibling[0]->attributes()->{'name'};

                    if (!isset($args[$name])) {
                        break;
                    }

                    if (is_object($args[$name])) {
                        $args[$name] = $args[$name]->getArrayCopy();
                    }

                    if (!is_array($args[$name])) {
                        throw new URFAException("$name can only be an array");
                    }

                    foreach ($args[$name] as $v) {
                        if (is_object($v)) {
                            $v = $v->getArrayCopy();
                        }
                        if (!is_array($v)) {
                            throw new URFAException('To tag "for" an array must be two-dimensional');
                        }

                        $this->processDataInput($node, $v);
                    }
                    break;
                case 'set':
                    if (!$dst = (string) $attr->{'dst'}) {
                        break;
                    }

                    if ($src = (string) $attr->{'src'}) {
                        $this->data_set_src[$dst] = $src;
                    } elseif ($val = (string) $attr->{'value'}) {
                        $this->data_set_value[$dst] = $val;
                    }
            }
        }
    }

    /**
     * Обработка скалярных типов данных
     *
     * @param \SimpleXMLElement $node Элемент дерева api.xml
     * @param array             $args Переданные аргументы метода
     * @param string            $type Тип данных integer|long|double|ip_address|string
     *
     * @return void
     * @throws URFAException
     */
    protected function processDataInputScalar(\SimpleXMLElement $node, array $args, $type)
    {
        $attr = $node->attributes();

        $name = (string) $attr->{'name'};

        $default = (isset($attr->{'default'})) ? (string) $attr->{'default'} : null;
        $default = ($default === 'now()') ? time() : $default;
        $default = ($default === 'max_time()') ? 2000000000 : $default;

        $sibling = $node->xpath('following-sibling::*[1]');
        $sibling = (isset($sibling[0])) ? $sibling[0] : false;

        if ($sibling and $sibling->getName() === 'for') {
            if (isset($args[$name]) and is_object($args[$name])) {
                $args[$name] = $args[$name]->getArrayCopy();
            }

            $this->data_input[] = [
                'name'  => $name,
                'value' => (isset($args[$name]) and is_array($args[$name])) ? count($args[$name]) : 0,
                'type'  => $type,
            ];

            return;
        }

        if (array_key_exists($name, $args)) {
            $valid = true;

            switch ($type) {
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
                default:
                    $valid = false;
            }

            if ($valid) {
                $value = $args[$name];
            } else {
                throw new URFAException("$name can only be a $type");
            }
        } else {
            if (!is_null($default)) {
                $value = $default;
            } else {
                throw new URFAException("Required parameter $name ($type)");
            }
        }

        $this->data_input[] = [
            'name'  => $name,
            'value' => $value,
            'type'  => $type,
        ];
    }

    /**
     * Рекурсивная функция обработки выходных параметров api.xml
     *
     * @param \SimpleXMLElement  $output Элемент дерева api.xml
     * @param Packet $packet Пакет с бинарными данными
     *
     * @return ArrayObject
     * @throws URFAException
     */
    protected function processDataOutput(\SimpleXMLElement $output, Packet $packet)
    {
        $result = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);

        foreach ($output->children() as $node) {
            $attr = $node->attributes();

            switch ($node->getName()) {
                case 'integer':
                    $result[(string) $attr->{'name'}] = $packet->getDataInt();
                    break;
                case 'long':
                    $result[(string) $attr->{'name'}] = $packet->getDataLong();
                    break;
                case 'double':
                    $result[(string) $attr->{'name'}] = $packet->getDataDouble();
                    break;
                case 'ip_address':
                    $result[(string) $attr->{'name'}] = $packet->getDataIp();
                    break;
                case 'string':
                    $result[(string) $attr->{'name'}] = $packet->getDataString();
                    break;

                case 'error':
                    $this->processDataError($node);
                    // no break
                case 'set':
                    $dst = (string) $node->attributes()->{'dst'};
                    $src = (string) $node->attributes()->{'src'};
                    $value = (string) $node->attributes()->{'value'};

                    if (!$dst) {
                        break;
                    }

                    if ($src and isset($result[$src])) {
                        $value = $result[$src];
                    }

                    $result[$dst] = $value;
                    break;

                case 'if':
                    $variable = (string) $attr->{'variable'};

                    $result_value = false;
                    foreach ($result as $k => $v) {
                        if ($k === $variable) {
                            $result_value = $v;
                            break;
                        }
                    }

                    if ($result_value === false) {
                        foreach ($this->data_input as $v) {
                            if ($v['name'] === $variable) {
                                $result_value = $v['value'];
                                break;
                            }
                        }
                    }
                    if ($result_value === false) {
                        break;
                    }

                    switch (gettype($result_value)) {
                        case 'integer':
                            $value = (int) $attr->{'value'};
                            break;
                        case 'double':
                            $value = (float) $attr->{'value'};
                            break;
                        case 'string':
                            $value = (string) $attr->{'value'};
                            break;
                        default:
                            throw new URFAException(
                                'Not provided an error, contact the developer (' . __FUNCTION__ . ')'
                            );
                    }

                    switch ((string) $attr->{'condition'}) {
                        case 'eq':
                            if ($result_value === $value) {
                                $result = new ArrayObject(array_merge(
                                    $result->getArrayCopy(),
                                    $this->processDataOutput($node, $packet)->getArrayCopy()
                                ), ArrayObject::ARRAY_AS_PROPS);
                            }
                            break;
                        case 'ne':
                            if ($result_value !== $value) {
                                $result = new ArrayObject(array_merge(
                                    $result->getArrayCopy(),
                                    $this->processDataOutput($node, $packet)->getArrayCopy()
                                ), ArrayObject::ARRAY_AS_PROPS);
                            }
                            break;
                    }
                    break;

                case 'for':
                    $sibling = $node->xpath("preceding-sibling::*[name()='integer' or name()='long'][1]");

                    if (!$sibling) {
                        $sibling = $node->xpath("parent::*[1]/preceding-sibling::*[name()='integer' or name()='long'][1]");
                    }

                    if (!isset($sibling[0])) {
                        throw new URFAException('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                    }

                    $name = (string) $sibling[0]->attributes()->{'name'};

                    if (isset($result[$name]) and is_object($result[$name])) {
                        break;
                    }

                    $count = (int) ((isset($result[$name]))
                        ? $result[$name]
                        : $this->data_output[$name]);

                    $array = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
                    for ($i = 0; $i < $count; $i++) {
                        $array->append($this->processDataOutput($node, $packet));
                    }
                    $result[$name] = $array;

                    break;
            }

            $this->data_output += $result->getArrayCopy();
        }

        return $result;
    }

    /**
     * Метод обработки элементов error в api.xml
     *
     * @param \SimpleXMLElement $node Элемент дерева api.xml
     *
     * @return void
     * @throws URFAException
     */
    protected function processDataError(\SimpleXMLElement $node)
    {
        $attr = $node->attributes();

        $code = (isset($attr->{'code'})) ? "Code: {$attr->{'code'}}" : '';
        $comment = (isset($attr->{'comment'})) ? "Comment: {$attr->{'comment'}}" : '';
        $variable = (isset($attr->{'variable'})) ? "Variable: {$attr->{'variable'}}" : '';

        throw new URFAException("XML Described error: $code $comment $variable");
    }

    /**
     * Освобождаем память от данных urfa функции
     *
     * @return self
     */
    protected function cleanData()
    {
        $this->data_input = [];
        $this->data_output = [];
        $this->data_set_src = [];
        $this->data_set_value = [];

        return $this;
    }
}
