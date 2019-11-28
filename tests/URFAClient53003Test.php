<?php

require_once 'URFAClient53Test.php';

/**
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class URFAClient53003Test extends URFAClient53Test
{
    protected $config = [
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'localhost',
        'protocol' => 'tls',
        'api'      => '../xml/api_53-003.xml',
    ];
}
