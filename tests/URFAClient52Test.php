<?php

include_once 'URFAClientBaseTest.php';

/**
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
class URFAClient52Test extends URFAClientBaseTest {

    protected $_config = array(
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'localhost',
        'api'      => __DIR__ . '/../xml/api_52-008.xml',
        'log'      => TRUE,
    );

}
