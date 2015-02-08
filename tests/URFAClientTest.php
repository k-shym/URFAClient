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
    public function test_rpcf_get_discount_periods()
    {
        $result = $this->_api->rpcf_get_discount_periods();

        $this->assertArrayHasKey('discount_periods_count', $result);
        $this->assertTrue((bool) count($result['discount_periods_count']));

        return $result['discount_periods_count'];
    }

    /**
     * @depends test_rpcf_liburfa_list
     */
    public function test_rpcf_add_user_new()
    {
        $result = $this->_api->rpcf_add_user_new(array(
            'login'            => 'user' . self::prefix(),
            'password'         => 'pass' . self::prefix(),
        ));

        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('basic_account', $result);

        return $result;
    }

    /**
     * @depends test_rpcf_add_user_new
     */
    public function test_rpcf_get_userinfo(array $user)
    {
        $result = $this->_api->rpcf_get_userinfo(array(
            'user_id' => $user['user_id'],
        ));

        $this->assertTrue((bool) $result);
        $this->assertEquals($user['user_id'], $result['user_id']);
        $this->assertEquals($user['basic_account'], $result['basic_account']);
        $this->assertEquals('user' . self::prefix(), $result['login']);
        $this->assertEquals('pass' . self::prefix(), $result['password']);
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

    /**
     * @depends test_rpcf_liburfa_list
     */
    public function test_rpcf_add_iptraffic_service_ex()
    {
        $result = $this->_api->rpcf_add_iptraffic_service_ex(array(
            'parent_id'            => 0,
            'tariff_id'            => 0,
            'service_name'         => 'service' . self::prefix(),
            'comment'              => 'Тестовая услуга',
            'link_by_default'      => 0,
            'is_dynamic'           => 0,
            'cost'                 => 0.13,
            'discount_method'      => 1,
            'sessions_limit'       => 0,
            'null_service_prepaid' => 0,
        ));

        $this->assertArrayHasKey('service_id', $result);
        $this->assertTrue($result['service_id'] > 0);

        return $result;
    }

    /**
     * @depends test_rpcf_add_iptraffic_service_ex
     */
    public function test_rpcf_get_iptraffic_service_ex(array $service)
    {
        $result = $this->_api->rpcf_get_iptraffic_service_ex(array(
            'sid' => $service['service_id'],
        ));

        $this->assertTrue((bool) $result);
        $this->assertEquals('service' . self::prefix(), $result['service_name']);
        $this->assertEquals('Тестовая услуга', $result['comment']);
        $this->assertEquals(1, $result['discount_method']);
    }

    /**
     * @depends test_rpcf_add_user_new
     * @depends test_rpcf_add_iptraffic_service_ex
     * @depends test_rpcf_get_discount_periods
     */
    public function test_rpcf_add_iptraffic_service_link_ipv6(array $user, array $service, array $discount_periods)
    {
        $discount_period = array_pop($discount_periods);

        $result = $this->_api->rpcf_add_iptraffic_service_link_ipv6(array(
            'user_id'            => $user['user_id'],
            'account_id'         => $user['basic_account'],
            'service_id'         => $service['service_id'],
            'tplink_id'          => 0,
            'discount_period_id' => $discount_period['discount_period_id'],
            'start_date'         => time(),
            'expire_date'        => 2000000000,
            'policy_id'          => 1,
            'unabon'             => 0,
            'unprepay'           => 0,
            'ip_groups_count'    => array(
                array(
                    'ip'             => long2ip(rand()),
                    'mask'           => 32,
                    'mac'            => '',
                    'login'          => 'inet4user' . self::prefix(),
                    'allowed_cid'    => '',
                    'password'       => 'inet4pass' . self::prefix(),
                    'is_skip_radius' => 0,
                    'is_skip_rfw'    => 0,
                    'router_id'      => 0,
                ),
                array(
                    'ip'             => implode(':', str_split(md5(rand()), 4)),
                    'mask'           => 32,
                    'mac'            => '',
                    'login'          => 'inet6user' . self::prefix(),
                    'allowed_cid'    => '',
                    'password'       => 'inet6pass' . self::prefix(),
                    'is_skip_radius' => 0,
                    'is_skip_rfw'    => 0,
                    'router_id'      => 0,
                ),
            ),
        ));

        $this->assertArrayHasKey('slink_id', $result);
        $this->assertTrue($result['slink_id'] > 0);

        return $result;
    }

    /**
     * @depends test_rpcf_add_iptraffic_service_link_ipv6
     * @depends test_rpcf_get_discount_periods
     */
    public function test_rpcf_get_iptraffic_service_link_ipv6(array $slink, array $discount_periods)
    {
        $result = $this->_api->rpcf_get_iptraffic_service_link_ipv6(array(
            'slink_id' => $slink['slink_id'],
        ));

        $discount_period = array_pop($discount_periods);

        $this->assertTrue((bool) $result);
        $this->assertEquals($discount_period['discount_period_id'], $result['discount_period_id']);
        $this->assertEquals(2000000000, $result['expire_date']);
        $this->assertTrue(is_array($result['ip_groups_count']));

        foreach ($result['ip_groups_count'] as $ip_group)
        {
            $this->assertTrue((bool) filter_var($ip_group['ip_address'], FILTER_VALIDATE_IP));
            $this->assertEquals(32, $ip_group['mask']);
        }
    }

    /**
     * @depends test_rpcf_add_user_new
     * @depends test_rpcf_add_iptraffic_service_ex
     * @depends test_rpcf_get_discount_periods
     */
    public function test_rpcf_add_iptraffic_service_link_ipv6_without_ip(array $user, array $service, array $discount_periods)
    {
        $discount_period = array_pop($discount_periods);

        $result = $this->_api->rpcf_add_iptraffic_service_link_ipv6(array(
            'user_id'            => $user['user_id'],
            'account_id'         => $user['basic_account'],
            'service_id'         => $service['service_id'],
            'tplink_id'          => 0,
            'discount_period_id' => $discount_period['discount_period_id'],
            'start_date'         => time(),
            'expire_date'        => strtotime('+1 month'),
            'policy_id'          => 1,
            'unabon'             => 0,
            'unprepay'           => 0,
        ));

        $this->assertArrayHasKey('slink_id', $result);
        $this->assertTrue($result['slink_id'] === -1);
    }

    /**
     * @dataProvider long
     */
    public function test_rpcf_add_fwrule_new($long)
    {
        $result = $this->_api->rpcf_add_fwrule_new(array(
            'flags'     => 0,
            'events'    => $long,
            'router_id' => 0,
            'tariff_id' => 0,
            'group_id'  => 0,
            'user_id'   => 0,
            'rule'      => 'ACCOUNT_ID',
            'comment'   => '',
        ));

        $this->assertArrayHasKey('rule_id', $result);
        $this->assertTrue($result['rule_id'] > 0);

        $rule_id = $result['rule_id'];

        $result = $this->_api->rpcf_get_fwrules_list_new();

        $this->assertArrayHasKey('rules_count', $result);

        foreach ($result['rules_count'] as $v)
            if ($v['rule_id'] == $rule_id)
                $this->assertEquals($long, $v['events']);
    }

    public function long()
    {
        return array(
          array('-922334069862591'),
          array(-9013),
          array(9013),
          array('922334069862591')
        );
    }

    public function test_rpcf_get_userinfo_not_user()
    {
        $this->assertFalse($this->_api->rpcf_get_userinfo(array(
            'user_id' => 0,
        )));
    }

    public function test_not_exist()
    {
        $this->assertFalse($this->_api->not_exist());
    }
}
