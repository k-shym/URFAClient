<?php

/**
 * Объект соединения с ядром UTM5
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
final class URFAClient_Connection {

    /**
     * @var Resource (stream)
     */
    protected $_socket;

    /**
     * @var integer
     */
    protected $_version = 35;

    /**
     * @var integer
     */
    protected $_code;

    /**
     * @var bool
     */
    public $ipv6 = TRUE;

    /**
     * Конструктор соединения
     *
     * @param array $data Конфигурация
     * @throws Exception
     */
    public function __construct(Array $data)
    {
        $context = stream_context_create();

        if ($data['protocol'] !== 'none')
        {
            if ($data['admin'] AND $data['protocol'] === 'ssl')
            {
                stream_context_set_option($context, 'ssl', 'capture_peer_cert', TRUE);
                stream_context_set_option($context, 'ssl', 'local_cert', __DIR__ . '/../../admin.crt');
                stream_context_set_option($context, 'ssl', 'passphrase', 'netup');
                stream_context_set_option($context, 'ssl', 'ciphers', 'SSLv3');
            }
            elseif ($data['protocol'] === 'tls' OR $data['protocol'] === 'ssl')
            {
                stream_context_set_option($context, 'ssl', 'ciphers', 'ADH-RC4-MD5');
            }

            stream_context_set_option($context, 'ssl', 'verify_peer', FALSE);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', FALSE);
        }


        $data['address'] = gethostbyname($data['address']);

        $this->_socket = stream_socket_client("tcp://{$data['address']}:{$data['port']}", $err_no, $err_str, $data['timeout'], STREAM_CLIENT_CONNECT, $context);

        if ( ! $this->_socket)
            throw new Exception("$err_str ($err_no)");

        stream_set_timeout($this->_socket, $data['timeout']);

        if ( ! $this->_auth($data['login'], $data['password'], $data['admin'], $data['protocol']))
            throw new Exception('Login or password incorrect');
    }

    /**
     * Аутентификация пользователя
     *
     * @param   string    $login
     * @param   string    $password
     * @param   bool      $admin
     * @param   string    $protocol
     * @return  bool
     * @throws Exception
     */
    protected function _auth($login, $password, $admin, $protocol)
    {
        $packet = $this->packet();

        while ( ! feof($this->_socket))
        {
            $packet->clean();
            $this->read($packet);

            switch ($this->_code)
            {
                case 192:
                    $digest = $packet->attr[6]['data'];
                    $ctx = hash_init('md5');
                    hash_update($ctx, $digest);
                    hash_update($ctx, $password);
                    $hash = hash_final($ctx, true);
                    $packet->clean();
                    $this->_code = 193;
                    $packet->set_attr_string($login, 2);
                    $packet->set_attr_string($digest, 8);
                    $packet->set_attr_string($hash, 9);
                    if ($protocol !== 'none')
                        $packet->set_attr_int(($protocol === 'ssl') ? (($admin) ? 4 : 2) : 6, 10);
                    else
                        $packet->set_attr_int(0, 10);
                    $packet->set_attr_int(2, 1);
                    $this->write($packet);
                    break;

                case 194:
                    $attr_protocol = $packet->get_attr_int(10);
                    if ($attr_protocol === 6)
                        stream_socket_enable_crypto($this->_socket, TRUE, STREAM_CRYPTO_METHOD_ANY_CLIENT);
                    elseif ($attr_protocol)
                        stream_socket_enable_crypto($this->_socket, TRUE, STREAM_CRYPTO_METHOD_SSLv3_CLIENT);

                    return TRUE;

                case 195: return FALSE;
            }
        }

        return FALSE;
    }

    /**
     * Вызов функции
     *
     * @param   integer    $code
     * @return  bool
     * @throws Exception
     */
    public function call($code)
    {
        $packet = $this->packet();
        $this->_code = 201;
        $packet->set_attr_int($code, 3);
        $this->write($packet);

        if ( ! feof($this->_socket))
        {
            $packet->clean();
            $this->read($packet);
            switch ($this->_code)
            {
                case 200:
                    if ($packet->get_attr_int(3) == $code) return TRUE;
                    else return FALSE;
            }
        }

        return FALSE;
    }

    /**
     * Результат вызова функции
     *
     * @return  URFAClient_Packet
     * @throws Exception
     */
    public function result()
    {
        $packet = $this->packet();

        while (TRUE)
        {
            if ( ! feof($this->_socket))
            {
                $this->read($packet);
                if ($packet->get_attr_int(4))
                    break;
            }
        }

        return $packet;
    }

    /**
     * Читаем данные из соединения
     *
     * @param   URFAClient_Packet $packet
     * @throws Exception
     */
    public function read(URFAClient_Packet $packet)
    {
        $this->_code = ord(fread($this->_socket, 1));

        if ( ! $this->_code)
            throw new Exception("Error code {$this->_code}");

        $version = ord(fread($this->_socket, 1));

        if ($version !== $this->_version)
            throw new Exception("Error code {$this->_code}. Version: $version");

        list(, $packet->len) = unpack('n', fread($this->_socket, 2));

        $len = 4;

        while ($len < $packet->len)
        {
            list(, $code) = unpack('s', fread($this->_socket, 2));
            list(, $length) = unpack('n', fread($this->_socket, 2));
            $len += $length;

            $data = ($length == 4) ? NULL :  fread($this->_socket, $length - 4);

            if ($code == 5)
            {
                $packet->data[] = $data;
            }
            else
            {
                $packet->attr[$code]['data'] = $data;
                $packet->attr[$code]['len'] = $length;
            }
        }
    }

    /**
     * Записываем данные в соединение
     *
     * @param   URFAClient_Packet $packet
     */
    public function write(URFAClient_Packet $packet)
    {
        fwrite($this->_socket, chr($this->_code));
        fwrite($this->_socket, chr($this->_version));
        fwrite($this->_socket, pack('n', $packet->len));

        foreach ($packet->attr as $code => $value)
        {
            fwrite($this->_socket, pack('v', $code));
            fwrite($this->_socket, pack('n', $value['len']));
            fwrite($this->_socket, $value['data']);
        }

        foreach ($packet->data as $code => $value)
        {
            fwrite($this->_socket, pack('v', 5));
            fwrite($this->_socket, pack('n', strlen($value) + 4));
            fwrite($this->_socket, $value);
        }
    }

    /**
     * Создает объект пакета
     *
     * @return  URFAClient_Packet
     */
    public function packet()
    {
        return new URFAClient_Packet($this->ipv6);
    }

    /**
     * Закрываем соединение при уничтожении объекта
     */
    public function __destruct()
    {
        if ($this->_socket) fclose($this->_socket);
    }
}