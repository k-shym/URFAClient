<?php

namespace Tests;

use URFAClient\API;
use ArrayObject;

/**
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class URFAClient53004Test extends URFAClient53Test
{
    protected $config = [
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'localhost',
        'protocol' => 'tls',
        'api'      => 'api_53-004.xml',
    ];


    public function testGetDiscountPeriods()
    {
        return parent::testGetDiscountPeriods();
    }

    /**
     * @return ArrayObject
     */
    public function testAddUser()
    {
        return parent::testAddUser();
    }

    /**
     * @depends testAddUser
     */
    public function testGetUserinfo(ArrayObject $user)
    {
        parent::testGetUserinfo($user);
    }

    /**
     * @depends testAddUser
     */
    public function testSearchUsers(ArrayObject $user)
    {
        parent::testSearchUsers($user);
    }

    /**
     * @depends testAddUser
     */
    public function testInitApiUser()
    {
        return parent::testInitApiUser();
    }

    /**
     * @depends testInitApiUser
     */
    public function testChangePassword(API $api)
    {
        parent::testChangePassword($api);
    }

    /**
     * @depends testInitApiUser
     */
    public function testEditUser(API $api)
    {
        parent::testEditUser($api);
    }

    /**
     * @depends testAddUser
     */
    public function testSaveUserOthersets(ArrayObject $user)
    {
        parent::testSaveUserOthersets($user);
    }

    public function testAddIptrafficService()
    {
        return parent::testAddIptrafficService();
    }

    /**
     * @depends testAddIptrafficService
     */
    public function testEditIptrafficService(ArrayObject $service)
    {
        return parent::testEditIptrafficService($service);
    }

    /**
     * @depends testEditIptrafficService
     */
    public function testGetIptrafficService(ArrayObject $service)
    {
        parent::testGetIptrafficService($service);
    }

    /**
     * @depends testAddUser
     * @depends testAddIptrafficService
     * @depends testGetDiscountPeriods
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
                    'id'             => 0,
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
                [
                    'id'             => 1,
                    'ip'             => implode(':', str_split(md5(rand()), 4)),
                    'mask'           => 32,
                    'mac'            => '',
                    'login'          => 'inet6user' . self::prefix(),
                    'allowed_cid'    => '',
                    'password'       => 'inet6pass' . self::prefix(),
                    'is_skip_radius' => 0,
                    'is_skip_rfw'    => 0,
                    'router_id'      => 0,
                ],
            ],
        ]);

        $this->assertArrayHasKey('slink_id', $result);
        $this->assertTrue($result['slink_id'] > 0);

        return $result;
    }

    /**
     * @depends testAddIptrafficServiceIpv6
     * @depends testGetDiscountPeriods
     */
    public function testGetIptrafficServiceIpv6(ArrayObject $slink, ArrayObject $discount_periods)
    {
        parent::testGetIptrafficServiceIpv6($slink, $discount_periods);
    }

    /**
     * @depends testAddIptrafficServiceIpv6
     */
    public function testSetRadiusAttr(ArrayObject $slink)
    {
        $radiusAttrs = [
            [
                'vendor'      => 100000,
                'attr'        => 1,
                'tag'         => 1,
                'usage_flags' => 1,
                'param1'      => 1,
                'val'         => 'c102400',
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
     */
    public function testAddIptrafficServiceIpv6WithoutIp(ArrayObject $user, ArrayObject $service, ArrayObject $discount_periods)
    {
        parent::testAddIptrafficServiceIpv6WithoutIp($user, $service, $discount_periods);
    }

    /**
     * @dataProvider long
     */
    public function testAddFwrule($long)
    {
        parent::testAddFwrule($long);
    }
}
