<?php
/**
 * Объект работы из командной строки
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class URFAClient_Cmd extends URFAClient_API
{
    /**
     * Возвращает функцию из api.xml в определённом виде
     *
     * @param string $name Имя функции
     * @param string $type Тип представления
     *
     * @return mixed
     * @throws Exception
     */
    public function method($name, $type = null)
    {
        $method = false;
        foreach ($this->api->function as $function) {
            if ((string) $function->attributes()->{'name'} === $name) {
                $method = $function;
                break;
            }
        }

        if (!$method) {
            throw new Exception("Function $name not found");
        }

        return ($type === 'xml')
            ? $method->asXML()
            : $this->_processOptionsInput($method->input);
    }

    /**
     * Возвращает список функций
     *
     * @return array
     */
    public function methods()
    {
        $list = [];
        foreach ($this->api->function as $function) {
            $attr = $function->attributes();
            $list[(string) $attr->{'name'}] = (string) $attr->{'id'};
        }

        asort($list);

        return $list;
    }

    /**
     * Рекурсивная функция обработки входных параметров api.xml
     *
     * @param SimpleXMLElement $input         Элемент дерева api.xml
     * @param array            $options_input Опции функции
     *
     * @return array
     * @throws Exception
     */
    protected function _processOptionsInput(SimpleXMLElement $input, array &$options_input = [])
    {
        foreach ($input->children() as $node) {
            $name = (string) $node->attributes()->{'name'};

            switch ($node->getName())
            {
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
                $this->_processOptionsInput($node, $options_input);
                break;
            case 'for':
                $sibling = $node->xpath('preceding-sibling::*[1]');

                if (!isset($sibling[0])) {
                    throw new Exception('Not provided an error, contact the developer (' . __FUNCTION__ . ')');
                }
                $options_input[(string) $sibling[0]->attributes()->{'name'}] = [$this->_processOptionsInput($node)];
                break;
            }
        }

        return $options_input;
    }
}
