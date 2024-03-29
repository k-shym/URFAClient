<?php

namespace Tests;

use URFAClient\URFAClient;
use URFAClient\API;
use ArrayObject;

/**
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
abstract class URFAClient53Test extends URFAClientBaseTest
{
    protected $config = [
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'localhost',
        'protocol' => 'tls',
        'api'      => 'api_53-003.xml',
    ];

    /**
     * @return array
     */
    public function testGetDiscountPeriods()
    {
        $result = $this->api->rpcf_get_discount_periods();

        $this->assertArrayHasKey('discount_periods_count', $result);
        $this->assertTrue((bool) $result->discount_periods_count->count());

        return $result['discount_periods_count'];
    }

    /**
     * @return ArrayObject
     */
    public function testAddUser()
    {
        $param = new ArrayObject([
            'login'    => 'user' . self::prefix(),
            'password' => 'pass' . self::prefix(),
        ]);
        $result = $this->api->rpcf_add_user_new($param);

        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('basic_account', $result);

        return $result;
    }

    /**
     * @depends testAddUser
     *
     * @return void
     */
    public function testGetUserinfo(ArrayObject $user)
    {
        $result = $this->api->rpcf_get_userinfo('{"user_id": ' . $user['user_id'] . '}');

        $this->assertTrue((bool) $result);
        $this->assertEquals($user['user_id'], $result['user_id']);
        $this->assertEquals($user['basic_account'], $result['basic_account']);
        $this->assertEquals('user' . self::prefix(), $result['login']);
        $this->assertEquals('pass' . self::prefix(), $result['password']);
    }

    /**
     * @depends testAddUser
     *
     * @return void
     */
    public function testSearchUsers(ArrayObject $user)
    {
        $result = $this->api->rpcf_search_users_new([
            'select_type'    => 0,
            'patterns_count' => [
                [
                    'what'        => 2,
                    'criteria_id' => 3,
                    'pattern'     => 'user' . self::prefix(),
                ],
            ],
        ]);

        $this->assertArrayHasKey('user_data_size', $result);
        $this->assertTrue(count($result['user_data_size']) === 1);
        $result = $result['user_data_size'][0];
        $this->assertEquals($user['user_id'], $result['user_id']);
        $this->assertEquals($user['basic_account'], $result['basic_account']);
        $this->assertEquals('user' . self::prefix(), $result['login']);
    }

    /**
     * @depends testAddUser
     *
     * @return void
     */
    public function testPaymentForAccount(ArrayObject $user)
    {
        $result = $this->api->rpcf_add_payment_for_account([
            'account_id' => $user->basic_account,
            'payment'    => 13,
            'comment'    => 'Тестовый платеж',
        ]);

        $this->assertArrayHasKey('payment_transaction_id', $result);
        $this->assertTrue($result->payment_transaction_id > 0);
    }

    /**
     * @depends testAddUser
     *
     * @return API
     * @throws \Exception
     */
    public function testInitApiUser()
    {
        $this->config['login'] = 'user' . self::prefix();
        $this->config['password'] = 'pass' . self::prefix();
        $this->config['admin'] = false;
        $api_user = URFAClient::init($this->config);

        $this->assertInstanceOf(API::class, $api_user);

        return $api_user;
    }

    /**
     * @depends testInitApiUser
     *
     * @return void
     */
    public function testChangePassword(API $api)
    {
         $result = $api->rpcf_user5_change_password([
             'old_password'     => 'pass' . self::prefix(),
             'new_password'     => 'pass' . self::prefix(),
             'new_password_ret' => 'pass' . self::prefix(),
         ]);

        $this->assertTrue($result['result'] > 0);
    }

    /**
     * @depends testInitApiUser
     *
     * @return void
     */
    public function testEditUser(API $api)
    {
        $data = [
            'full_name'         => 'full_name' . self::prefix(),
            'actual_address'    => 'actual_address' . self::prefix(),
            'juridical_address' => 'juridical_address' . self::prefix(),
            'work_telephone'    => 'work_telephone' . self::prefix(),
            'home_telephone'    => 'home_telephone' . self::prefix(),
            'mobile_telephone'  => 'mobile_telephone' . self::prefix(),
            'web_page'          => 'web_page' . self::prefix(),
            'icq_number'        => 'icq_number' . self::prefix(),
            'pasport'           => 'pasport' . self::prefix(),
            'bank_id'           => 0,
            'bank_account'      => 'bank_account' . self::prefix(),
            'email'             => 'email' . self::prefix(),
        ];

        $api->rpcf_user5_edit_user($data);
        $result = $api->rpcf_user5_get_user_info_new();

        foreach ($data as $k => $v) {
            $this->assertEquals($result[$k], $v);
        }
    }

    /**
     * @depends testInitApiUser
     *
     * @return void
     */
    public function testAccountsPromisedPayment(API $api)
    {
        $accounts = $api->rpcf_user5_get_accounts();
        $this->assertArrayHasKey('accounts_size', $accounts);

        foreach ($accounts->accounts_size as $account) {
            $result = $api->rpcf_user5_get_promised_payment(['account_id' => $account->account_id]);
            $this->assertArrayHasKey('res', $result);
            if ($result->res !== -1) {
                $this->assertArrayHasKey('last_payment_date', $result);
                $this->assertArrayHasKey('amount', $result);
            }
        }
    }

    /**
     * @depends testAddUser
     *
     * @return void
     */
    public function testSaveUserOthersets(ArrayObject $user)
    {
        $this->assertInstanceOf(ArrayObject::class, $this->api->rpcf_save_user_othersets([
            'user_id' => $user['user_id'],
            'count'   => [
                [
                    'type'        => 3,
                    'currency_id' => 978,
                ],
            ],
        ]));
    }

    /**
     * @return ArrayObject
     */
    public function testAddIptrafficService()
    {
        $result = $this->api->rpcf_add_iptraffic_service_ex([
            'parent_id'            => 0,
            'tariff_id'            => 0,
            'service_name'         => 'service' . self::prefix(),
            'comment'              => 'Тестовая услуга',
            'link_by_default'      => 0,
            'is_dynamic'           => 0,
            'cost'                 => 0,
            'discount_method'      => 1,
            'sessions_limit'       => 0,
            'null_service_prepaid' => 0,
        ]);

        $this->assertArrayHasKey('service_id', $result);
        $this->assertTrue($result['service_id'] > 0);

        return $result;
    }

    /**
     * @depends testAddIptrafficService
     * @return  ArrayObject
     */
    public function testEditIptrafficService(ArrayObject $service)
    {
        $result = $this->api->rpcf_edit_iptraffic_service_ex([
            'service_id'           => $service['service_id'],
            'parent_id'            => 0,
            'tariff_id'            => 0,
            'service_name'         => 'service' . self::prefix(),
            'comment'              => 'Тестовая услуга',
            'link_by_default'      => 0,
            'is_dynamic'           => 0,
            'cost'                 => 0.13,
            'discount_method_t'    => 1,
            'sessions_limit'       => 0,
            'null_service_prepaid' => 0,
            'num_of_borders' => new ArrayObject([
                new ArrayObject([
                    'tclass_b' => 1,
                    'size_b' => 2,
                    'cost_b' => 1.5,
                ]),
            ]),
            'num_of_prepaid' => new ArrayObject([
                [
                    'tclass_p' => 1,
                    'size_p' => 2,
                    'size_max_p' => 3,
                ],
            ]),
        ]);

        $this->assertArrayHasKey('service_id', $result);
        $this->assertEquals($result['service_id'], $service['service_id']);

        return $result;
    }

    /**
     * @depends testEditIptrafficService
     * @return  void
     */
    public function testGetIptrafficService(ArrayObject $service)
    {
        $result = $this->api->rpcf_get_iptraffic_service_ex([
            'sid' => $service['service_id'],
        ]);

        $this->assertTrue((bool) $result);
        $this->assertEquals('service' . self::prefix(), $result['service_name']);
        $this->assertEquals('Тестовая услуга', $result['comment']);
        $this->assertEquals(1, $result['discount_method']);
        $this->assertEquals(0.13, $result['cost']);
        $this->assertEquals(1, $result->borders_count->count());
        $this->assertEquals(1, $result->prepaid_count->count());
    }

    /**
     * @depends testAddUser
     * @depends testAddIptrafficService
     * @depends testGetDiscountPeriods
     *
     * @return ArrayObject
     */
    public function testAddIptrafficServiceIpv6(ArrayObject $user, ArrayObject $service, ArrayObject $discount_periods)
    {
        $discount_period = $discount_periods->offsetGet(0);

        $result = $this->api->rpcf_add_iptraffic_service_link_ipv6([
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
            'ip_groups_count'    => [
                [
                    'ip'             => long2ip(rand()),
                    'mask'           => 32,
                    'mac'            => '',
                    'login'          => 'inet4user' . self::prefix(),
                    'allowed_cid'    => '',
                    'password'       => 'inet4pass' . self::prefix(),
                    'is_skip_radius' => 0,
                    'is_skip_rfw'    => 0,
                    'router_id'      => 0,
                ],
                new ArrayObject([
                    'ip'             => implode(':', str_split(md5(rand()), 4)),
                    'mask'           => 32,
                    'mac'            => '',
                    'login'          => 'inet6user' . self::prefix(),
                    'allowed_cid'    => '',
                    'password'       => 'inet6pass' . self::prefix(),
                    'is_skip_radius' => 0,
                    'is_skip_rfw'    => 0,
                    'router_id'      => 0,
                ]),
            ],
        ]);

        $this->assertArrayHasKey('slink_id', $result);
        $this->assertTrue($result['slink_id'] > 0);

        return $result;
    }

    /**
     * @depends testAddIptrafficServiceIpv6
     * @depends testGetDiscountPeriods
     *
     * @return void
     */
    public function testGetIptrafficServiceIpv6(ArrayObject $slink, ArrayObject $discount_periods)
    {
        $result = $this->api->rpcf_get_iptraffic_service_link_ipv6([
            'slink_id' => $slink['slink_id'],
        ]);

        $discount_period = $discount_periods->offsetGet(0);

        $this->assertTrue((bool) $result);
        $this->assertEquals($discount_period['discount_period_id'], $result['discount_period_id']);
        $this->assertEquals(2000000000, $result['expire_date']);
        $this->assertInstanceOf(ArrayObject::class, $result['ip_groups_count']);

        foreach ($result['ip_groups_count'] as $ip_group) {
            $this->assertTrue((bool) filter_var($ip_group['ip_address'], FILTER_VALIDATE_IP));
            $this->assertEquals(32, $ip_group['mask']);
        }
    }

    /**
     * @depends testAddIptrafficServiceIpv6
     * @return void
     */
    public function testSetRadiusAttr(ArrayObject $slink)
    {
        $radiusAttrs = [
            [
                'vendor'      => 100000,
                'attr'        => 1,
                'usage_flags' => 1,
                'param1'      => 3,
                'val'        => '102400',
            ],
        ];

        $this->api->rpcf_set_radius_attr([
            'sid' => $slink['slink_id'],
            'st'  => 10000,
            'cnt' => $radiusAttrs,
        ]);

        $result = $this->api->rpcf_get_radius_attr([
            'sid' => $slink['slink_id'],
            'st'  => 10000,
        ]);

        $this->assertTrue(count($result['radius_data_size']) === count($radiusAttrs));
        foreach ($result['radius_data_size'] as $k => $v) {
            $this->assertTrue($v['vendor'] === $radiusAttrs[$k]['vendor']);
            $this->assertTrue($v['attr'] === $radiusAttrs[$k]['attr']);
            $this->assertTrue($v['usage_flags'] === $radiusAttrs[$k]['usage_flags']);
            $this->assertTrue($v['param1'] === $radiusAttrs[$k]['param1']);
            $this->assertTrue($v['val'] === $radiusAttrs[$k]['val']);
        }
    }

    /**
     * @depends testAddUser
     * @depends testAddIptrafficService
     * @depends testGetDiscountPeriods
     *
     * @return void
     */
    public function testAddIptrafficServiceIpv6WithoutIp(ArrayObject $user, ArrayObject $service, ArrayObject $discount_periods)
    {
        $discount_period = $discount_periods->offsetGet(0);

        $result = $this->api->rpcf_add_iptraffic_service_link_ipv6([
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
        ]);

        $this->assertArrayHasKey('slink_id', $result);
        $this->assertTrue($result['slink_id'] === -1);
    }

    /**
     * @dataProvider long
     *
     * @return void
     */
    public function testAddFwrule($long)
    {
        $result = $this->api->rpcf_add_fwrule_new([
            'flags'     => 0,
            'events'    => $long,
            'router_id' => 0,
            'tariff_id' => 0,
            'group_id'  => 0,
            'user_id'   => 0,
            'rule'      => 'ACCOUNT_ID',
            'comment'   => '',
        ]);

        $this->assertArrayHasKey('rule_id', $result);
        $this->assertTrue($result['rule_id'] > 0);

        $rule_id = $result['rule_id'];

        $result = $this->api->rpcf_get_fwrules_list_new();

        $this->assertArrayHasKey('rules_count', $result);

        foreach ($result['rules_count'] as $v) {
            if ($v['rule_id'] == $rule_id) {
                $this->assertEquals($long, $v['events']);
            }
        }
    }

    /**
     * @return array
     */
    public function long()
    {
        return [
            ['-922334069862591'],
            [-9013],
            [9013],
            ['922334069862591'],
        ];
    }
}
