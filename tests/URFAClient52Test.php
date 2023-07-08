<?php

namespace Tests;

/**
 * Тесты для версии сервера 5.2
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
abstract class URFAClient52Test extends URFAClientBaseTest
{
    protected $config = [
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'localhost',
        'protocol' => 'ssl',
        'api'      => 'api_52-008.xml',
    ];
}
