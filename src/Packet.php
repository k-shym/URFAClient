<?php

namespace URFAClient;

/**
 * Объект для подготовки получения/отправки бинарных данных ядру
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class Packet
{
    /**
     * Поддрежка IPv6 сервером
     *
     * @var bool
     */
    protected $ipv6;

    /**
     * Длина пакета
     *
     * @var integer
     */
    public $len = 4;

    /**
     * Счетчик пакета
     *
     * @var integer
     */
    public $iterator = 0;

    /**
     * Атрибуты пакета
     *
     * @var array
     */
    public $attr = [];

    /**
     * Данные пакета
     *
     * @var array
     */
    public $data = [];

    /**
     * Конструктор пакета
     *
     * @param bool $ipv6 Поддержка IPv6
     *
     * @throws URFAException
     */
    public function __construct($ipv6)
    {
        $this->ipv6 = $ipv6;
    }

    /**
     * Записать целое число в атрибут пакета
     *
     * @param integer $data Целое число
     * @param integer $code Код атрибута
     *
     * @return $this
     */
    public function setAttrInt($data, $code)
    {
        $this->attr[$code]['data'] = pack('N', $data);
        $this->attr[$code]['len'] = 8;
        $this->len += 8;

        return $this;
    }

    /**
     * Получить целое число из атрибутов пакета
     *
     * @param integer $code Код атрибута
     *
     * @return mixed
     */
    public function getAttrInt($code)
    {
        return (isset($this->attr[$code]['data']))
            ? $this->bin2int($this->attr[$code]['data'])
            : false;
    }

    /**
     * Записать строку в атрибут пакет
     *
     * @param string  $data Строка
     * @param integer $code Код атрибута
     *
     * @return $this
     */
    public function setAttrString($data, $code)
    {
        $this->attr[$code]['data'] = $data;
        $this->attr[$code]['len'] = strlen($data) + 4;
        $this->len += strlen($data) + 4;

        return $this;
    }

    /**
     * Добавить целое число в пакет
     *
     * @param integer $data Целое число
     *
     * @return $this
     */
    public function setDataInt($data)
    {
        $this->data[] = pack('N', $data);
        $this->len += 8;

        return $this;
    }

    /**
     * Получить целое число
     *
     * @return integer
     */
    public function getDataInt()
    {
        return $this->bin2int($this->data[$this->iterator++]);
    }

    /**
     * Добавить число с плавающей точкой в пакет
     *
     * @param float $data Число с плавающей точкой
     *
     * @return $this
     */
    public function setDataDouble($data)
    {
        $this->data[] = strrev(pack('d', $data));
        $this->len += 12;

        return $this;
    }

    /**
     * Получить число с плавающей точкой
     *
     * @return float
     */
    public function getDataDouble()
    {
        $data = unpack('d', strrev($this->data[$this->iterator++]));
        if (!$data) {
            return null;
        }
        return (float) $data[1];
    }

    /**
     * Записать строку в пакет
     *
     * @param string $data Строка
     *
     * @return $this
     */
    public function setDataString($data)
    {
        $this->data[] = $data;
        $this->len += strlen($data) + 4;

        return $this;
    }

    /**
     * Получить строку из пакета
     *
     * @return string
     */
    public function getDataString()
    {
        return (string) $this->data[$this->iterator++];
    }

    /**
     * Записать IP адрес в пакет
     *
     * @param string $data IP адрес
     *
     * @return $this
     */
    public function setDataIp($data)
    {
        $data = (($this->ipv6)
            ? pack("C", (filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? 4 : 6)
            : ''
        ) . inet_pton($data);
        $this->data[] = $data;
        $this->len += strlen($data) + 4;

        return $this;
    }

    /**
     * Получить IP адрес из пакета
     *
     * @return string
     */
    public function getDataIp()
    {
        $data = $this->data[$this->iterator++];
        return (string) inet_ntop(($this->ipv6) ? substr($data, 1) : $data);
    }

    /**
     * Записать bigint в пакет
     *
     * @param string $data Большое целое число
     *
     * @return $this
     * @throws URFAException
     */
    public function setDataLong($data)
    {
        $hi = ($data >> 32) & 0xffffffff;
        $lo = $data & 0xffffffff;

        if ($lo & 0x80000000) {
            $hi = ( ! $hi) ? 0xffffffff : $hi;
        }

        $this->data[] = pack('N2', $hi, $lo);
        $this->len += 12;

        return $this;
    }

    /**
     * Получить bigint из пакета
     *
     * @return string
     */
    public function getDataLong()
    {
        $data = unpack('N2', $this->data[$this->iterator++]);

        if (!$data) {
            return null;
        }

        return ($data[1] << 32) | $data[2];
    }

    /**
     * Преобразовать бинарные данные в число
     *
     * @param string $data Бианрые данные
     *
     * @return integer
     */
    protected function bin2int($data)
    {
        $data = unpack('N', $data);

        if (!$data) {
            return null;
        }

        if ($data[1] & 0x80000000) {
            return $data[1] - (0xffffffff + 1);
        }

        return (int) $data[1];
    }

    /**
     * Приводим пакет к исходному состоянию
     *
     * @return $this
     */
    public function clean()
    {
        $this->len = 4;
        $this->iterator = 0;
        $this->attr = [];
        $this->data = [];

        return $this;
    }
}
