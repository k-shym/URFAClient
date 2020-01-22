<?php

namespace UrfaClient\Common;

use UrfaClient\Config\UrfaConfig;
use UrfaClient\Exception\UrfaAuthException;
use UrfaClient\Exception\UrfaClientException;
use UrfaClient\Exception\UrfaConnectException;

/**
 * Объект соединения с ядром UTM5
 *
 * @license https://github.com/k-shym/UrfaClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 */
final class UrfaConnection
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
     * @var UrfaConfig
     */
    protected $config;

    /**
     * @var bool
     */
    public $ipv6 = true;

    /**
     * UrfaConnection constructor.
     *
     * @param UrfaConfig $config Конфигурация
     */
    public function __construct(UrfaConfig $config)
    {
        $this->setConfig($config);
    }

    public function isConnected(): bool
    {
        return is_resource($this->socket);
    }

    /**
     * @return UrfaConnection
     */
    public function connect(): UrfaConnection
    {

        $context = stream_context_create();

        if ($this->config->isAdmin() && $this->config->getProtocol() === 'ssl') {
            stream_context_set_option($context, 'ssl', 'capture_peer_cert', true);
            stream_context_set_option($context, 'ssl', 'local_cert', __DIR__.'/../../admin.crt');
            stream_context_set_option($context, 'ssl', 'passphrase', 'netup');
            stream_context_set_option($context, 'ssl', 'ciphers', 'SSLv3');
        } elseif ($this->config->getProtocol() === 'tls' || $this->config->getProtocol() === 'ssl') {
            stream_context_set_option($context, 'ssl', 'ciphers', 'ADH-RC4-MD5');
        }

        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        stream_context_set_option($context, 'ssl', 'verify_peer_name', false);

        $this->config->setHost(gethostbyname($this->config->getHost()));
        try {
            $this->socket = stream_socket_client(
                "tcp://{$this->config->getHost()}:{$this->config->getPort()}",
                $err_no,
                $err_str,
                $this->config->getTimeout(),
                STREAM_CLIENT_CONNECT,
                $context
            );
        } catch (\Exception $e) {
        }
        if (!$this->socket) {
            throw new UrfaConnectException("$err_str ($err_no)");
        }

        stream_set_timeout($this->socket, $this->config->getTimeout());


        if (!$this->auth($this->config)) {
            throw new UrfaAuthException('Login or password incorrect');
        }

        return $this;
    }

    /**
     * Аутентификация пользователя
     *
     * @param   UrfaConfig $config
     * @return  bool
     * @throws  UrfaClientException
     */
    protected function auth($config): bool
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
                    hash_update($ctx, $config->getPassword());
                    $hash = hash_final($ctx, true);
                    $packet->clean();
                    $this->code = 193;
                    $packet->setAttrString($config->getLogin(), 2);
                    // Восстанавливать авторизацию из сессии
                    if ($config->getSession() !== null) {
                        $packet->setAttrString(pack('H32', $config->getSession()), 6);
                    } else {
                        $config->setSession(bin2hex($digest));
                    }
                    $packet->setAttrString($digest, 8);
                    $packet->setAttrString($hash, 9);
                    $packet->setAttrInt(($config->getProtocol() === 'ssl') ? ($config->isAdmin() ? 4 : 2) : 6, 10);
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
     * @throws  UrfaClientException
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
     * @return  UrfaPacket
     * @throws  UrfaClientException
     */
    public function result(): UrfaPacket
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
     * @param   UrfaPacket $packet
     * @throws  UrfaClientException
     */
    public function read(UrfaPacket $packet): void
    {
        $this->debug("READ <= ");

        $this->code = ord(fread($this->socket, 1));
        $this->debug(sprintf('code: %d ', $this->code));

        if (!$this->code) {
            throw new UrfaClientException("Error code {$this->code}");
        }

        $version = ord(fread($this->socket, 1));
        $this->debug(sprintf('version: %d ', $version));

        if ($version !== $this->version) {
            throw new UrfaClientException("Error code {$this->code}. Version: $version");
        }

        list(, $packet->len) = unpack('n', fread($this->socket, 2));
        $this->debug(sprintf('packet_len: %d ', $packet->len));

        $len = 4;

        while ($len < $packet->len) {
            list(, $code) = unpack('s', fread($this->socket, 2));
            list(, $length) = unpack('n', fread($this->socket, 2));
            $len += $length;

            $data = ($length === 4) ? null : fread($this->socket, $length - 4);
            $this->debug(sprintf("\n PACKET code: %d len: %d ", $code, $length), $data);

            if ($code === 5) {
                $packet->data[] = $data;
            } else {
                $packet->attr[$code]['data'] = $data;
                $packet->attr[$code]['len'] = $length;
            }
        }
        $this->debug("\n");
    }

    /**
     * Записываем данные в соединение
     *
     * @param   UrfaPacket $packet
     */
    public function write(UrfaPacket $packet): void
    {
        $this->debug(sprintf("WRITE => code: %d version: %d packet_len: %d ", $this->code, $this->version, $packet->len));
        fwrite($this->socket, chr($this->code));
        fwrite($this->socket, chr($this->version));
        fwrite($this->socket, pack('n', $packet->len));

        foreach ($packet->attr as $code => $value) {
            $this->debug(sprintf("\n PACKET code: %d len: %d ", $code, $value['len']), $value['data']);
            fwrite($this->socket, pack('v', $code));
            fwrite($this->socket, pack('n', $value['len']));
            fwrite($this->socket, $value['data']);
        }

        foreach ($packet->data as $code => $value) {
            $this->debug(sprintf("\n PACKET code: %d len: %d ", 5, strlen($value) + 4), $value);
            fwrite($this->socket, pack('v', 5));
            fwrite($this->socket, pack('n', strlen($value) + 4));
            fwrite($this->socket, $value);
        }
        $this->debug("\n");
    }

    /**
     * Создает объект пакета
     *
     * @return  UrfaPacket
     */
    public function packet(): UrfaPacket
    {
        return new UrfaPacket($this->ipv6);
    }

    /**
     * @return UrfaConfig
     */
    public function getConfig(): UrfaConfig
    {
        return $this->config;
    }


    /**
     * @param $config
     * @return UrfaConnection
     */
    public function setConfig(UrfaConfig $config): UrfaConnection
    {
        $this->config = $config;

        return $this;
    }

    public function close()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
    }

    /**
     * Закрываем соединение при уничтожении объекта
     */
    public function __destruct()
    {
        $this->close();
    }

    private function debug(string $string, $data = '')
    {
        if ($this->config->isDebug()) {
            echo $string;
            if ($data) {
                echo 'data:';
                for ($i = 0; $i < strlen($data); ++$i) {
                    printf(' %02X', ord($data[$i]));
                }
                echo ' | '.preg_replace('#[^[:print:]]#', ' ', $data);
            }
        }
    }
}
