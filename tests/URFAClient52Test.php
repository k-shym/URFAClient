<?php

require_once 'URFAClientBaseTest.php';

/**
 * Тесты для версии сервера 5.2
 *
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class URFAClient52Test extends URFAClientBaseTest
{
    protected $config = [
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'localhost',
        'log'      => true,
    ];
}
