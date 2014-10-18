<?php

include_once 'URFAClientBaseTest.php';

/**
 * Класс тестов текущей версии api.xml
 *
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md
 * @author  Konstantin Shum <k.shym@ya.ru>
 */
class URFAClientTest extends URFAClientBaseTest {

    protected $_config = array(
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'bill.example.org',
        'log'      => TRUE,
    );

    public function test_rpcf_liburfa_list()
    {
        $this->assertTrue((bool) count($this->_api->rpcf_liburfa_list()));
    }

    /**
     * @depends test_rpcf_liburfa_list
     */
    public function test_rpcf_add_user_new()
    {
        $result = $this->_api->rpcf_add_user_new(array(
            'login'            => 'user' . self::prefix(),
            'password'         => 'pass' . self::prefix(),
            'parameters_count' => array(),
            'groups_count'     => array(),
        ));

        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('basic_account', $result);

        return $result;
    }

    /**
     * @depends test_rpcf_add_user_new
     */
    public function test_rpcf_save_user_othersets(array $user)
    {
        $this->assertTrue(is_array($this->_api->rpcf_save_user_othersets(array(
            'user_id' => $user['user_id'],
            'count'   => array(
                array(
                    'type'        => 3,
                    'currency_id' => 978,
                ),
            ),
        ))));
    }

    public function test_not_exist()
    {
        $this->assertFalse($this->_api->not_exist());
    }
}
