<?php

/**
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
class URFAClient_Cmd extends URFAClient_API {

    /**
     * Возвращает функцию из api.xml в определённом виде
     *
     * @param  string $name   Имя функции
     * @param  string $type   Тип представления
     * @return mixed
     */
    public function method($name, $type = NULL)
    {
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

        return ($type === 'xml') ? $method->asXML() : $this->_proccess_options_input($method->input);
    }

    /**
     * Возвращает список функций
     *
     * @return Array
     */
    public function listing()
    {
        $list = array();
        foreach ($this->_api->function as $function)
        {
            $attr = $function->attributes();
            $list[(string) $attr->{'name'}] = (string) $attr->{'id'};
        }

        asort($list);

        return $list;
    }

    /**
     * Рекурсивная функция обработки входных параметров api.xml
     *
     * @param  SimpleXMLElement  $input           Элемент дерева api.xml
     * @param  Array             $options_input   Опции функции
     * @return Array
     */
    protected function _proccess_options_input(SimpleXMLElement $input, Array &$options_input = array())
    {
        foreach ($input->children() as $node)
        {
            $name = (string) $node->attributes()->{'name'};

            switch ($node->getName())
            {
                case 'integer':
                case 'long': $options_input[$name] = 0; break;
                case 'double': $options_input[$name] = 0.0; break;
                case 'ip_address': $options_input[$name] = '0.0.0.0'; break;
                case 'string': $options_input[$name] = ''; break;
                case 'if': $this->_proccess_options_input($node, $options_input); break;
                case 'for':
                    $sibling = $node->xpath('preceding-sibling::*[1]');

                    if ( ! isset($sibling[0])) throw new Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');

                    $options_input[(string) $sibling[0]->attributes()->{'name'}] = array($this->_proccess_options_input($node));
                    break;
            }
        }

        return $options_input;
    }
}
