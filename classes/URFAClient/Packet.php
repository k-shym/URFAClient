<?php

/**
 * Объект для подготовки получения/отправки бинарных данных ядру
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
class URFAClient_Packet {

    /**
     * @var Boolean
     */
    public static $ipv6 = TRUE;

    /**
     * @var Int     Длина пакета
     */
    public $_len = 4;

    /**
     * @var Int     Счетчик пакета
     */
    public $_iterator = 0;

    /**
     * @var Array     Атрибуты пакета
     */
    public $_attr = array();

    /**
     * @var Array     Данные пакета
     */
    public $_data = array();

    /**
     * @param   Int                 $data
     * @param   Int                 $code
     * @return  URFAClient_Packet
     */
    public function set_attr_int($data, $code)
    {
        $this->_attr[$code]['data'] = pack('N', $data);
        $this->_attr[$code]['len'] = 8;
        $this->_len += 8;

        return $this;
    }

    /**
     * @param   Int       $code
     * @return  Mixed
     */
    public function get_attr_int($code)
    {
        return (isset($this->_attr[$code]['data'])) ? $this->_bin2int($this->_attr[$code]['data']) : FALSE;
    }

    /**
     * @param   String              $data
     * @param   Int                 $code
     * @return  URFAClient_Packet
     */
    public function set_attr_string($data, $code)
    {
        $this->_attr[$code]['data'] = $data;
        $this->_attr[$code]['len'] = strlen($data) + 4;
        $this->_len += strlen($data) + 4;

        return $this;
    }

    /**
     * @param   Int                 $data
     * @return  URFAClient_Packet
     */
    public function set_data_int($data)
    {
        $this->_data[] = pack('N', $data);
        $this->_len += 8;

        return $this;
    }

    /**
     * @return Int
     */
    public function get_data_int()
    {
        return $this->_bin2int($this->_data[$this->_iterator++]);
    }

    /**
     * @param   Float               $data
     * @return  URFAClient_Packet
     */
    public function set_data_double($data)
    {
        $this->_data[] = strrev(pack('d', $data));
        $this->_len += 12;

        return $this;
    }

    /**
     * @return Float
     */
    public function get_data_double()
    {
        $data = unpack('d', strrev($this->_data[$this->_iterator++]));
        return (float) $data[1];
    }

    /**
     * @param   String              $data
     * @return  URFAClient_Packet
     */
    public function set_data_string($data)
    {
        $this->_data[] = $data;
        $this->_len += strlen($data) + 4;

        return $this;
    }

    /**
     * @return String
     */
    public function get_data_string()
    {
        return (string) $this->_data[$this->_iterator++];
    }

    /**
     * @param   String              $data
     * @return  URFAClient_Packet
     */
    public function set_data_ip($data)
    {
        $data = ((self::$ipv6) ? pack("C", (filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? 4 : 6) : '') . inet_pton($data);
        $this->_data[] = $data;
        $this->_len += strlen($data) + 4;

        return $this;
    }

    /**
     * @return  String
     */
    public function get_data_ip()
    {
        $data = $this->_data[$this->_iterator++];
        return (string) inet_ntop((self::$ipv6) ? substr($data, 1) : $data);
    }

    /**
     * @param   String                 $data
     * @return  URFAClient_Packet
     */
    public function set_data_long($data)
    {
        if (PHP_INT_SIZE == 4)
        {
            throw new Exception('Not implemented for PHP x32');
        }
        else
        {
            $hi = bcdiv($data, 0xffffffff + 1);
            $lo = bcmod($data, 0xffffffff + 1);

            if ($hi & 0x80000000)
            {
                $hi = $hi & 0xffffffff - 1;
                $lo = $lo & 0xffffffff;
            }

            if ($lo & 0x80000000)
            {
                $lo = $lo & 0xffffffff;
                $hi = ( ! $hi) ? 0xffffffff : $hi;
            }
        }

        $this->_data[] = pack('N2', $hi, $lo);
        $this->_len += 12;

        return $this;
    }

    /**
     * @return String
     */
    public function get_data_long()
    {
        $data = unpack('N2', $this->_data[$this->_iterator++]);

        if (PHP_INT_SIZE == 4)
        {
            $hi = $data[1];
            $lo = $data[2];
            $neg = $hi < 0;

            if ($neg)
            {
                $hi = ~$hi & (int) 0xffffffff;
                $lo = ~$lo & (int) 0xffffffff;

                if ($lo == (int) 0xffffffff)
                {
                    $hi++;
                    $lo = 0;
                }
                else
                {
                    $lo++;
                }
            }

            if ($hi & (int) 0x80000000)
            {
                $hi &= (int) 0x7fffffff;
                $hi += 0x80000000;
            }

            if ($lo & (int) 0x80000000)
            {
                $lo &= (int) 0x7fffffff;
                $lo += 0x80000000;
            }

            $value = bcmul($hi, 0xffffffff + 1);
            $value = bcadd($value, $lo);

            if ($neg) $value = bcsub(0, $value);
        }
        else
        {
            if ($data[1] & 0x80000000)
            {
                $data[1] = $data[1] & 0xffffffff;
                $data[1] = $data[1] ^ 0xffffffff;
                $data[2] = $data[2] ^ 0xffffffff;
                $data[2] = $data[2] + 1;

                $value = bcmul($data[1], 0xffffffff + 1);
                $value = bcsub(0, $value);
                $value = bcsub($value, $data[2]);
            }
            else
            {
                $value = bcmul($data[1], 0xffffffff + 1);
                $value = bcadd($value, $data[2]);
            }
        }

        return $value;
    }

    /**
     * @param   String      Бианрые данные
     * @return  Int
     */
    protected function _bin2int($data)
    {
        $array = unpack('N', $data);

        // для 64-х битной версии php
        if ($array[1] >= 0x80000000)
            return $array[1] - (0xffffffff + 1);

        return (int) $array[1];
    }

    /**
     * Приводим пакет к исходному состоянию
     *
     * @return URFAClient_Packet
     */
    public function clean()
    {
        $this->_len = 4;
        $this->_iterator = 0;
        $this->_attr = array();
        $this->_data = array();

        return $this;
    }
}