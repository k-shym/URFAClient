<?php

namespace UrfaClient\Client;

use SimpleXMLElement;
use UrfaClient\Common\Connection;
use UrfaClient\Common\Packet;
use UrfaClient\Exception\UrfaClientException;

/**
 * Объект предоставляет обращение к функциям из api.xml
 *
 * @license https://github.com/k-shym/UrfaClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 *
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 *
 */
class UrfaClientApi extends UrfaClientAbstract
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var SimpleXMLElement
     */
    protected $apiXmlContent;

    /**
     * @var array
     */
    protected $dataInput = [];

    /**
     * @var array
     */
    protected $dataOutput = [];


    /**
     * Конструктор класса
     *
     * @param  string $apiXmlFile Путь до файла api
     * @param  Connection $connection Объект соединения с ядром
     * @throws UrfaClientException
     */
    public function __construct($apiXmlFile, Connection $connection = null)
    {
        $this->connection = $connection;

        if (!file_exists($apiXmlFile)) {
            throw new \InvalidArgumentException("File $apiXmlFile not found");
        }

        $this->apiXmlContent = simplexml_load_string(file_get_contents($apiXmlFile));

        if (!$this->apiXmlContent->xpath("/urfa/function[contains(@name, 'ipv6')]")) {
            $this->connection->ipv6 = false;
        }
    }

    /**
     * Магический метод для вызова функций из api.xml
     *
     * @param   string $name
     * @param   array $args
     * @return  array
     * @throws  UrfaClientException
     */
    public function __call(string $name, array $args)
    {
        if (!$this->connection) {
            throw new UrfaClientException("No object UrfaClient\Common\Connection");
        }

        $this->dataInput = $this->dataOutput = [];

        $method = false;
        foreach ($this->apiXmlContent->function as $function) {
            if ((string)$function->attributes()->{'name'} === $name) {
                $method = $function;
                break;
            }
        }

        if (!$method) {
            throw new UrfaClientException("Function $name not found");
        }

        $args = (isset($args[0]) and is_array($args[0])) ? (array)$args[0] : [];

        $this->processDataInput($method->input, $args);

        $code = (string)$method->attributes()->{'id'};
        $code = (strpos($code, '-') === 0) ? -1 * hexdec($code) : hexdec($code);

        if (!$this->connection->call($code)) {
            throw new UrfaClientException("Error calling function $name");
        }

        $packet = $this->connection->packet();
        foreach ($this->dataInput as $v) {
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
                    throw new UrfaClientException('Not provided an error, contact the developer ('.__FUNCTION__.')');
            }
        }
        if ($this->dataInput) {
            $this->connection->write($packet);
        }


        return $this->processDataOutput($method->output, $this->connection->result());
    }

    /**
     * Рекурсивная функция обработки входных параметров api.xml
     *
     * @param SimpleXMLElement $input Элемент дерева api.xml
     * @param array $args Переданные аргументы метода
     * @throws UrfaClientException
     */
    protected function processDataInput(SimpleXMLElement $input, Array $args)
    {
        foreach ($input->children() as $node) {
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
                    break;

                case 'if':
                    $attr = $node->attributes();
                    $variable = (string)$attr->{'variable'};

                    foreach ($this->dataInput as $v) {
                        if ($v['name'] === $variable) {
                            $variable = $v;
                            break;
                        }
                    }

                    if (!\is_array($variable)) {
                        throw new UrfaClientException('Not provided an error, contact the developer ('.__FUNCTION__.')');
                    }

                    switch ($variable['type']) {
                        case 'integer':
                            $value = (int)$attr->{'value'};
                            break;
                        case 'double':
                            $value = (float)$attr->{'value'};
                            break;
                        default:
                            $value = (string)$attr->{'value'};
                    }

                    switch ((string)$attr->{'condition'}) {
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
                    $sibling = $node->xpath('preceding-sibling::integer[1]');

                    if (!isset($sibling[0])) {
                        throw new UrfaClientException('Not provided an error, contact the developer ('.__FUNCTION__.')');
                    }

                    $name = (string)$sibling[0]->attributes()->{'name'};

                    if (!isset($args[$name])) {
                        break;
                    }

                    if (!is_array($args[$name])) {
                        throw new UrfaClientException("$name can only be an array");
                    }

                    foreach ($args[$name] as $v) {
                        if (!is_array($v)) {
                            throw new UrfaClientException('To tag "for" an array must be two-dimensional');
                        }

                        $this->processDataInput($node, $v);
                    }
                    break;
            }
        }
    }

    /**
     * Обработка скалярных типов данных
     *
     * @param SimpleXMLElement $node Элемент дерева api.xml
     * @param array $args Переданные аргументы метода
     * @param string $type Тип данных (integer|long|double|ip_address|string)
     * @throws UrfaClientException
     */
    protected function processDataInputScalar(SimpleXMLElement $node, array $args, string $type)
    {
        $attr = $node->attributes();

        $name = (string)$attr->{'name'};

        $default = isset($attr->{'default'}) ? (string)$attr->{'default'} : null;
        $default = ($default === 'now()') ? time() : $default;
        $default = ($default === 'max_time()') ? 2000000000 : $default;

        $sibling = $node->xpath('following-sibling::*[1]');
        $sibling = isset($sibling[0]) ? $sibling[0] : false;

        if ($sibling and $sibling->getName() === 'for') {
            $this->dataInput[] = [
                'name' => $name,
                'value' => (isset($args[$name]) and \is_array($args[$name])) ? \count($args[$name]) : 0,
                'type' => $type,
            ];

            return;
        }

        if (array_key_exists($name, $args)) {
            $valid = true;

            switch ($type) {
                case 'integer':
                    $args[$name] = (int)$args[$name];
                    break;
                case 'double':
                    $args[$name] = (float)$args[$name];
                    break;
                case 'ip_address':
                    $valid = (bool)filter_var($args[$name], FILTER_VALIDATE_IP);
                    break;
                case 'long':
                case 'string':
                    $args[$name] = (string)$args[$name];
                    break;
                default:
                    $valid = false;
            }

            if ($valid) {
                $value = $args[$name];
            } else {
                throw new UrfaClientException("$name can only be a $type");
            }
        } else {
            if ($default !== null) {
                $value = $default;
            } else {
                throw new UrfaClientException("Required parameter $name ($type)");
            }
        }

        $this->dataInput[] = [
            'name' => $name,
            'value' => $value,
            'type' => $type,
        ];
    }

    /**
     * Рекурсивная функция обработки выходных параметров api.xml
     *
     * @param SimpleXMLElement $output Элемент дерева api.xml
     * @param Packet $packet Пакет с бинарными данными
     * @return array
     * @throws UrfaClientException
     */
    protected function processDataOutput(SimpleXMLElement $output, Packet $packet): array
    {
        $result = [];

        foreach ($output->children() as $node) {
            $attr = $node->attributes();

            switch ($node->getName()) {
                case 'integer':
                    $result[(string)$attr->{'name'}] = $packet->getDataInt();
                    break;
                case 'long':
                    $result[(string)$attr->{'name'}] = $packet->getDataLong();
                    break;
                case 'double':
                    $result[(string)$attr->{'name'}] = $packet->getDataDouble();
                    break;
                case 'ip_address':
                    $result[(string)$attr->{'name'}] = $packet->getDataIp();
                    break;
                case 'string':
                    $result[(string)$attr->{'name'}] = $packet->getDataString();
                    break;

                case 'error':
                    $this->processDataError($node);
                    break;

                case 'set':
                    $dst = (string)$node->attributes()->{'dst'};
                    $src = (string)$node->attributes()->{'src'};
                    $value = (string)$node->attributes()->{'value'};

                    if (!$dst) {
                        break;
                    }

                    if ($src and isset($result[$src])) {
                        $value = $result[$src];
                    }

                    $result[$dst] = $value;
                    break;

                case 'if':
                    $variable = (string)$attr->{'variable'};

                    $result_value = false;
                    foreach ($result as $k => $v) {
                        if ($k === $variable) {
                            $result_value = $v;
                            break;
                        }
                    }

                    if ($result_value === false) {
                        foreach ($this->dataInput as $v) {
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
                            $value = (int)$attr->{'value'};
                            break;
                        case 'double':
                            $value = (float)$attr->{'value'};
                            break;
                        case 'string':
                            $value = (string)$attr->{'value'};
                            break;
                        default:
                            throw new UrfaClientException('Not provided an error, contact the developer ('.__FUNCTION__.')');
                    }

                    switch ((string)$attr->{'condition'}) {
                        case 'eq':
                            if ($result_value === $value) {
                                $result = array_merge($result, $this->processDataOutput($node, $packet));
                            }
                            break;

                        case 'ne':
                            if ($result_value !== $value) {
                                $result = array_merge($result, $this->processDataOutput($node, $packet));
                            }
                            break;
                    }
                    break;

                case 'for':
                    $sibling = $node->xpath('preceding-sibling::integer[1]');

                    if (!$sibling) {
                        $sibling = $node->xpath('parent::*[1]/preceding-sibling::integer[1]');
                    }

                    if (!isset($sibling[0])) {
                        throw new UrfaClientException('Not provided an error, contact the developer ('.__FUNCTION__.')');
                    }

                    $name = (string)$sibling[0]->attributes()->{'name'};

                    if (isset($result[$name]) and is_array($result[$name])) {
                        break;
                    }

                    $count = (int)(isset($result[$name]) ? $result[$name] : $this->dataOutput[$name]);
                    $array = [];
                    for ($i = 0; $i < $count; $i++) {
                        $array[] = $this->processDataOutput($node, $packet);
                    }

                    $result[$name] = $array;

                    break;
            }

            $this->dataOutput += $result;
        }

        return $result;
    }

    /**
     * Метод обработки элементов error в api.xml
     *
     * @param SimpleXMLElement $node
     * @throws UrfaClientException
     */
    protected function processDataError(SimpleXMLElement $node): void
    {
        $attr = $node->attributes();

        $code = isset($attr->{'code'}) ? "Code: {$attr->{'code'}}" : '';
        $comment = isset($attr->{'comment'}) ? "Comment: {$attr->{'comment'}}" : '';
        $variable = isset($attr->{'variable'}) ? "Variable: {$attr->{'variable'}}" : '';

        throw new UrfaClientException("XML Described error: $code $comment $variable");
    }

    /**
     * Рекурсивная функция обработки входных параметров api.xml
     *
     * @param  SimpleXMLElement $input Элемент дерева api.xml
     * @param  array $options_input Опции функции
     * @return array
     * @throws UrfaClientException
     */
    protected function processOptionsInput(SimpleXMLElement $input, array &$options_input = []): array
    {
        foreach ($input->children() as $node) {
            $name = (string)$node->attributes()->{'name'};

            switch ($node->getName()) {
                case 'integer':
                case 'long':
                    $options_input[$name] = 0;
                    break;
                case 'double':
                    $options_input[$name] = 0.0;
                    break;
                case 'ip_address':
                    $options_input[$name] = '0.0.0.0';
                    break;
                case 'string':
                    $options_input[$name] = '';
                    break;
                case 'if':
                    $this->processOptionsInput($node, $options_input);
                    break;
                case 'for':
                    $sibling = $node->xpath('preceding-sibling::*[1]');

                    if (!isset($sibling[0])) {
                        throw new UrfaClientException('Not provided an error, contact the developer ('.__FUNCTION__.')');
                    }

                    $options_input[(string)$sibling[0]->attributes()->{'name'}] = [$this->processOptionsInput($node)];
                    break;
            }
        }

        return $options_input;
    }

    /**
     * Возвращает функцию из api.xml в определённом виде
     *
     * @param  string $name Имя функции
     * @param  string|null $type Тип представления
     *
     * @return array|mixed
     * @throws UrfaClientException
     */
    public function method(string $name, string $type = null)
    {
        $method = false;
        foreach ($this->apiXmlContent->function as $function) {
            if ((string)$function->attributes()->{'name'} === $name) {
                $method = $function;
                break;
            }
        }

        if (!$method) {
            throw new UrfaClientException("Function $name not found");
        }

        return ($type === 'xml') ? $method->asXML() : $this->processOptionsInput($method->input);
    }

    /**
     * Возвращает список функций
     *
     * @return array
     */
    public function methods(): array
    {
        $list = [];
        foreach ($this->apiXmlContent->function as $function) {
            $attr = $function->attributes();
            $list[(string)$attr->{'name'}] = (string)$attr->{'id'};
        }

        asort($list);

        return $list;
    }
}
