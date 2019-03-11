<?php

namespace UrfaClient\Common;

use UrfaClient\Exception\UrfaClientException;

/**
 * Объект соединения с ядром UTM5
 *
 * @license https://github.com/k-shym/UrfaClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
final class Connection
{

    /**
     * @var Resource (stream)
     */
    protected $socket;

    /**
     * @var integer
     */
    protected $version = 35;

    /**
     * @var integer
     */
    protected $code;

    /**
     * @var bool
     */
    public $ipv6 = true;

    /**
     * Конструктор соединения
     *
     * @param array $data Конфигурация
     * @throws UrfaClientException
     */
    public function __construct(array $data)
    {
        $context = stream_context_create();

        if ($data['admin'] and $data['protocol'] === 'ssl') {
            stream_context_set_option($context, 'ssl', 'capture_peer_cert', true);
            stream_context_set_option($context, 'ssl', 'local_cert', __DIR__.'/../../admin.crt');
            stream_context_set_option($context, 'ssl', 'passphrase', 'netup');
            stream_context_set_option($context, 'ssl', 'ciphers', 'SSLv3');
        } elseif ($data['protocol'] === 'tls' or $data['protocol'] === 'ssl') {
            stream_context_set_option($context, 'ssl', 'ciphers', 'ADH-RC4-MD5');
        }

        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        stream_context_set_option($context, 'ssl', 'verify_peer_name', false);

        $data['address'] = gethostbyname($data['address']);

        $this->socket = stream_socket_client("tcp://{$data['address']}:{$data['port']}", $err_no, $err_str, $data['timeout'], STREAM_CLIENT_CONNECT, $context);

        if (!$this->socket) {
            throw new UrfaClientException("$err_str ($err_no)");
        }

        stream_set_timeout($this->socket, $data['timeout']);

        if (!$this->auth($data['login'], $data['password'], $data['admin'], $data['protocol'])) {
            throw new UrfaClientException('Login or password incorrect');
        }
    }

    /**
     * Аутентификация пользователя
     *
     * @param   string $login
     * @param   string $password
     * @param   bool $admin
     * @param   string $protocol
     * @return  bool
     * @throws UrfaClientException
     */
    protected function auth($login, $password, $admin, $protocol): bool
    {
        $packet = $this->packet();

        while (!feof($this->socket)) {
            $packet->clean();
            $this->read($packet);

            switch ($this->code) {
                case 192:
                    $digest = $packet->attr[6]['data'];
                    $ctx = hash_init('md5');
                    hash_update($ctx, $digest);
                    hash_update($ctx, $password);
                    $hash = hash_final($ctx, true);
                    $packet->clean();
                    $this->code = 193;
                    $packet->setAttrString($login, 2);
                    $packet->setAttrString($digest, 8);
                    $packet->setAttrString($hash, 9);
                    $packet->setAttrInt(($protocol === 'ssl') ? ($admin ? 4 : 2) : 6, 10);
                    $packet->setAttrInt(2, 1);
                    $this->write($packet);
                    break;

                case 194:
                    $attr_protocol = $packet->getAttrInt(10);
                    if ($attr_protocol === 6) {
                        stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
                    } elseif ($attr_protocol) {
                        stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_SSLv3_CLIENT);
                    }

                    return true;

                case 195:
                    return false;
            }
        }

        return false;
    }

    /**
     * Вызов функции
     *
     * @param   integer $code
     * @return  bool
     * @throws UrfaClientException
     */
    public function call($code): bool
    {
        $packet = $this->packet();
        $this->code = 201;
        $packet->setAttrInt($code, 3);
        $this->write($packet);

        if (!feof($this->socket)) {
            $packet->clean();
            $this->read($packet);
            switch ($this->code) {
                case 200:
                    return $packet->getAttrInt(3) === $code;
            }
        }

        return false;
    }

    /**
     * Результат вызова функции
     *
     * @return  Packet
     * @throws UrfaClientException
     */
    public function result(): Packet
    {
        $packet = $this->packet();

        while (true) {
            if (!feof($this->socket)) {
                $this->read($packet);
                if ($packet->getAttrInt(4)) {
                    break;
                }
            }
        }

        return $packet;
    }

    /**
     * Читаем данные из соединения
     *
     * @param   Packet $packet
     * @throws UrfaClientException
     */
    public function read(Packet $packet): void
    {
        $this->code = ord(fread($this->socket, 1));

        if (!$this->code) {
            throw new UrfaClientException("Error code {$this->code}");
        }

        $version = ord(fread($this->socket, 1));

        if ($version !== $this->version) {
            throw new UrfaClientException("Error code {$this->code}. Version: $version");
        }

        list(, $packet->len) = unpack('n', fread($this->socket, 2));

        $len = 4;

        while ($len < $packet->len) {
            list(, $code) = unpack('s', fread($this->socket, 2));
            list(, $length) = unpack('n', fread($this->socket, 2));
            $len += $length;

            $data = ($length === 4) ? null : fread($this->socket, $length - 4);

            if ($code === 5) {
                $packet->data[] = $data;
            } else {
                $packet->attr[$code]['data'] = $data;
                $packet->attr[$code]['len'] = $length;
            }
        }
    }

    /**
     * Записываем данные в соединение
     *
     * @param   Packet $packet
     */
    public function write(Packet $packet): void
    {
        fwrite($this->socket, chr($this->code));
        fwrite($this->socket, chr($this->version));
        fwrite($this->socket, pack('n', $packet->len));

        foreach ($packet->attr as $code => $value) {
            fwrite($this->socket, pack('v', $code));
            fwrite($this->socket, pack('n', $value['len']));
            fwrite($this->socket, $value['data']);
        }

        foreach ($packet->data as $code => $value) {
            fwrite($this->socket, pack('v', 5));
            fwrite($this->socket, pack('n', strlen($value) + 4));
            fwrite($this->socket, $value);
        }
    }

    /**
     * Создает объект пакета
     *
     * @return  Packet
     */
    public function packet(): Packet
    {
        return new Packet($this->ipv6);
    }

    /**
     * Закрываем соединение при уничтожении объекта
     */
    public function __destruct()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
    }
}
