<?php

namespace Tests;

/**
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/main/LICENSE.md GPLv3
 */
class URFAClient55002Test extends URFAClient55Test
{
    protected $config = [
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'utm',
        'api'      => __DIR__ . '/../xml/api_55-002.xml',
    ];
}
