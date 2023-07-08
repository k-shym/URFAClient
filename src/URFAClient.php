<?php

namespace URFAClient;

/**
 * Главный класс
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
abstract class URFAClient
{
    const API_XML = 'api_53-006.xml';

    /**
     * Метод инициализации
     *
     * @param array $data Массив с параметрами
     *
     * @return API
     * @throws URFAException
     */
    public static function init(array $data)
    {
        $data = array_merge([
            'login'    => 'init',
            'password' => 'init',
            'address'  => 'localhost',
            'port'     => 11758,
            'timeout'  => 30,
            'protocol' => 'auto',
            'admin'    => true,
            'api'      => self::API_XML,
        ], $data);

        return new API($data['api'], new Connection($data));
    }
}
