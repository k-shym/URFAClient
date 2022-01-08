<?php

namespace Tests;

use URFAClient\API;
use ArrayObject;

/**
 * @package URFAClient
 * @author  Konstantin Shum <k.shym@ya.ru>
 * @license https://github.com/k-shym/URFAClient/blob/master/LICENSE.md GPLv3
 */
class URFAClient53005Test extends URFAClient53004Test
{
    protected $config = [
        'login'    => 'init',
        'password' => 'init',
        'address'  => 'localhost',
        'protocol' => 'auto',
        'api'      => __DIR__ . '/../xml/api_53-005.xml',
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
        $result = $this->api->rpcf_add_iptraffic_service_ex([
            'parent_id'            => 0,
            'tariff_id'            => 0,
            'service_name'         => 'service' . self::prefix(),
            'comment'              => 'Тестовая услуга',
            'link_by_default'      => 0,
            'contract_type'        => 0,
            'is_dynamic'           => 0,
            'cost'                 => 0,
            'discount_method'      => 1,
            'sessions_limit'       => 0,
            'scheme_id'            => 0,
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
            'service_id'           => $service->service_id,
            'parent_id'            => 0,
            'tariff_id'            => 0,
            'service_name'         => 'service' . self::prefix(),
            'comment'              => 'Тестовая услуга',
            'link_by_default'      => 0,
            'contract_type'        => 0,
            'is_dynamic'           => 0,
            'cost'                 => 0.13,
            'discount_method_t'    => 1,
            'sessions_limit'       => 0,
            'scheme_id'            => 0,
            'null_service_prepaid' => 0,
            'num_of_borders' => [
                [
                    'tclass_b' => 1,
                    'size_b' => 2,
                    'cost_b' => 1.5,
                ],
            ],
            'num_of_prepaid' => [
                [
                    'tclass_p' => 1,
                    'size_p' => 2,
                    'size_max_p' => 3,
                ],
            ],
        ]);

        $this->assertArrayHasKey('service_id', $result);
        $this->assertEquals($result['service_id'], $service->service_id);

        return $result;
    }

    /**
     * @depends testEditIptrafficService
     * @return  void
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
        return parent::testAddIptrafficServiceIpv6($user, $service, $discount_periods);
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
