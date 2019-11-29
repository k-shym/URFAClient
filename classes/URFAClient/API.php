<?php
/**
 * Объект предоставляет обращение к функциям из api.xml
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class URFAClient_API extends URFAClient_Function
{
    /**
     * Объект соединения
     *
     * @var URFAClient_Connection
     */
    protected $connection;

    /**
     * Объект XML API файла
     *
     * @var SimpleXMLElement
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
     * Конструктор класса
     *
     * @param string                $api        Путь до файла api
     * @param URFAClient_Connection $connection Объект соединения с ядром
     *
     * @throws URFAClient_Exception
     */
    public function __construct($api, URFAClient_Connection $connection = null)
    {
        $this->connection = $connection;

        if (!file_exists($api)) {
            throw new URFAClient_Exception("File $api not found");
        }

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
     * @return array
     * @throws URFAClient_Exception
     */
    public function __call($name, $args)
    {
        if (!$this->connection) {
            throw new URFAClient_Exception("No object URFAClient_Connection");
        }


        $this->data_input = $this->data_output = [];

        $method = false;
        foreach ($this->api->function as $function) {
            if ((string) $function->attributes()->{'name'} === $name) {
                $method = $function;
                break;
            }
        }

        if (!$method) {
            throw new URFAClient_Exception("Function $name not found");
        }
        $args = (isset($args[0]) AND is_array($args[0])) ? (array) $args[0] : [];

        $this->processDataInput($method->input, $args);

        $code = (string) $method->attributes()->{'id'};
        $code = ($code{0} === '-') ? -1 * hexdec($code) : hexdec($code);

        if (!$this->connection->call($code)) {
            throw new URFAClient_Exception("Error calling function $name");
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
                throw new URFAClient_Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
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
     * @param SimpleXMLElement $input Элемент дерева api.xml
     * @param array            $args  Переданные аргументы метода
     *
     * @return void
     * @throws URFAClient_Exception
     */
    protected function processDataInput(SimpleXMLElement $input, array $args)
    {
        foreach ($input->children() as $node)
        {
            switch ($node->getName())
            {
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
                $variable = (string) $attr->{'variable'};

                foreach ($this->data_input as $v) {
                    if ($v['name'] === $variable) {
                        $variable = $v;
                        break;
                    }
                }

                if (!is_array($variable)) {
                    throw new URFAClient_Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                }

                switch ($variable['type'])
                {
                case 'integer':
                    $value = (int) $attr->{'value'};
                    break;
                case 'double':
                    $value = (float) $attr->{'value'};
                    break;
                default:
                    $value = (string) $attr->{'value'};
                }

                switch ((string) $attr->{'condition'})
                {
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
                    throw new URFAClient_Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                }

                $name = (string) $sibling[0]->attributes()->{'name'};

                if (!isset($args[$name])) {
                    break;
                }

                if (!is_array($args[$name])) {
                    throw new URFAClient_Exception("$name can only be an array");
                }

                foreach ($args[$name] as $v) {
                    if (!is_array($v)) {
                        throw new URFAClient_Exception('To tag "for" an array must be two-dimensional');
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
     * @param array            $args Переданные аргументы метода
     * @param string           $type Тип данных integer|long|double|ip_address|string
     *
     * @return void
     * @throws URFAClient_Exception
     */
    protected function processDataInputScalar(SimpleXMLElement $node, array $args, $type)
    {
        $attr = $node->attributes();

        $name = (string) $attr->{'name'};

        $default = (isset($attr->{'default'})) ? (string) $attr->{'default'} : null;
        $default = ($default === 'now()') ? time() : $default;
        $default = ($default === 'max_time()') ? 2000000000 : $default;

        $sibling = $node->xpath('following-sibling::*[1]');
        $sibling = (isset($sibling[0])) ? $sibling[0] : false;

        if ($sibling AND $sibling->getName() === 'for') {
            $this->data_input[] = [
                'name'  => $name,
                'value' => (isset($args[$name]) AND is_array($args[$name])) ? count($args[$name]) : 0,
                'type'  => $type,
            ];

            return;
        }

        if (array_key_exists($name, $args)) {
            $valid = true;

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
            default:
                $valid = false;
            }

            if ($valid) {
                $value = $args[$name];
            } else {
                throw new URFAClient_Exception("$name can only be a $type");
            }
        } else {
            if (!is_null($default)) {
                $value = $default;
            } else {
                throw new URFAClient_Exception("Required parameter $name ($type)");
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
     * @param SimpleXMLElement  $output Элемент дерева api.xml
     * @param URFAClient_Packet $packet Пакет с бинарными данными
     *
     * @return array
     * @throws URFAClient_Exception
     */
    protected function processDataOutput(SimpleXMLElement $output, URFAClient_Packet $packet)
    {
        $result = [];

        foreach ($output->children() as $node) {
            $attr = $node->attributes();

            switch ($node->getName())
            {
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
                break;

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

                switch (gettype($result_value))
                {
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
                    throw new URFAClient_Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                }

                switch ((string) $attr->{'condition'})
                {
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
                    throw new URFAClient_Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                }

                $name = (string) $sibling[0]->attributes()->{'name'};

                if (isset($result[$name]) and is_array($result[$name])) {
                    break;
                }

                $count = (int) ((isset($result[$name]))
                    ? $result[$name]
                    : $this->data_output[$name]);

                $array = [];
                for ($i=0; $i<$count; $i++) {
                    $array[] = $this->processDataOutput($node, $packet);
                }
                $result[$name] = $array;

                break;
            }

            $this->data_output += $result;
        }

        return $result;
    }

    /**
     * Метод обработки элементов error в api.xml
     *
     * @param SimpleXMLElement $node Элемент дерева api.xml
     *
     * @return void
     * @throws URFAClient_Exception
     */
    protected function processDataError(SimpleXMLElement $node)
    {
        $attr = $node->attributes();

        $code = (isset($attr->{'code'})) ? "Code: {$attr->{'code'}}" : '';
        $comment = (isset($attr->{'comment'})) ? "Comment: {$attr->{'comment'}}" : '';
        $variable = (isset($attr->{'variable'})) ? "Variable: {$attr->{'variable'}}" : '';

        throw new URFAClient_Exception("XML Described error: $code $comment $variable");
    }
}