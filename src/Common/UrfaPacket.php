<?php

namespace UrfaClient\Common;

use UrfaClient\Exception\UrfaClientException;

/**
 * Объект для подготовки получения/отправки бинарных данных ядру
 *
 * @license https://github.com/k-shym/UrfaClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
class UrfaPacket
{

    /**
     * @var bool
     */
    protected $ipv6;

    /**
     * @var integer  Длина пакета
     */
    public $len = 4;

    /**
     * @var integer  Счетчик пакета
     */
    public $iterator = 0;

    /**
     * @var array    Атрибуты пакета
     */
    public $attr = [];

    /**
     * @var array    Данные пакета
     */
    public $data = [];

    public function __construct($ipv6)
    {
        $this->ipv6 = $ipv6;
    }

    /**
     * @param int $data
     * @param int $code
     * @return UrfaPacket
     */
    public function setAttrInt(int $data, int $code): UrfaPacket
    {
        $this->attr[$code]['data'] = pack('N', $data);
        $this->attr[$code]['len'] = 8;
        $this->len += 8;

        return $this;
    }

    /**
     * @param int $code
     * @return bool|int|null
     */
    public function getAttrInt(int $code)
    {
        return isset($this->attr[$code]['data']) ? $this->bin2int($this->attr[$code]['data']) : false;
    }

    /**
     * @param string $data
     * @param int $code
     * @return UrfaPacket
     */
    public function setAttrString(string $data, int $code): UrfaPacket
    {
        $this->attr[$code]['data'] = $data;
        $this->attr[$code]['len'] = strlen($data) + 4;
        $this->len += strlen($data) + 4;

        return $this;
    }

    /**
     * @param int $data
     * @return UrfaPacket
     */
    public function setDataInt(int $data): UrfaPacket
    {
        $this->data[] = pack('N', $data);
        $this->len += 8;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDataInt(): ?int
    {
        return $this->bin2int($this->data[$this->iterator++]);
    }

    /**
     * @param   float $data
     * @return  UrfaPacket
     */
    public function setDataDouble($data): UrfaPacket
    {
        $this->data[] = strrev(pack('d', $data));
        $this->len += 12;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getDataDouble(): ?float
    {
        $data = unpack('d', strrev($this->data[$this->iterator++]));

        if (!$data) {
            return null;
        }

        return (float)$data[1];
    }

    /**
     * @param string $data
     * @return $this
     */
    public function setDataString(string $data): UrfaPacket
    {
        $this->data[] = $data;
        $this->len += strlen($data) + 4;

        return $this;
    }

    /**
     * @return string
     */
    public function getDataString(): string
    {
        return (string)$this->data[$this->iterator++];
    }

    /**
     * @param string $data
     * @return UrfaPacket
     */
    public function setDataIp(string $data): UrfaPacket
    {
        $data = ($this->ipv6 ? pack("C", filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 : 6) : '').inet_pton($data);
        $this->data[] = $data;
        $this->len += strlen($data) + 4;

        return $this;
    }

    /**
     * @return string
     */
    public function getDataIp(): string
    {
        $data = $this->data[$this->iterator++];

        return (string)inet_ntop($this->ipv6 ? substr($data, 1) : $data);
    }

    /**
     * @param string $data
     * @return UrfaPacket
     * @throws UrfaClientException
     */
    public function setDataLong(string $data): UrfaPacket
    {
        if (PHP_INT_SIZE === 4) {
            throw new UrfaClientException('Not implemented for PHP x32');
        }

        $hi = ($data >> 32) & 0xffffffff;
        $lo = $data & 0xffffffff;

        if ($lo & 0x80000000) {
            $hi = (!$hi) ? 0xffffffff : $hi;
        }

        $this->data[] = pack('N2', $hi, $lo);
        $this->len += 12;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDataLong(): ?string
    {
        if (PHP_INT_SIZE === 4) {
            throw new UrfaClientException('Not implemented for PHP x32');
        }

        $data = unpack('N2', $this->data[$this->iterator++]);

        if (!$data) {
            return null;
        }

        $hi = $data[1];
        $lo = $data[2];

        return ($hi << 32) & $lo;
    }

    /**
     * @param string $bin Бинарные данные
     * @return int|null
     */
    protected function bin2int(string $bin): ?int
    {
        $data = unpack('N', $bin);

        if (!$data) {
            return null;
        }

        // для 64-х битной версии php
        if ($data[1] >= 0x80000000) {
            return $data[1] - (0xffffffff + 1);
        }

        return (int)$data[1];
    }

    /**
     * Приводим пакет к исходному состоянию
     *
     * @return UrfaPacket
     */
    public function clean(): UrfaPacket
    {
        $this->len = 4;
        $this->iterator = 0;
        $this->attr = [];
        $this->data = [];

        return $this;
    }
}
