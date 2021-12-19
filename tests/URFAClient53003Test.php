<?php

namespace Tests;

use URFAClient\API;

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
        'api'      => __DIR__ . '/../xml/api_53-003.xml',
    ];

    public function testGetDiscountPeriods()
    {
        return parent::testGetDiscountPeriods();
    }

    /**
     * @return array
     */
    public function testAddUser()
    {
        return parent::testAddUser();
    }

    /**
     * @depends testAddUser
     */
    public function testGetUserinfo(array $user)
    {
        parent::testGetUserinfo($user);
    }

    /**
     * @depends testAddUser
     */
    public function testSearchUsers(array $user)
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
    public function testSaveUserOthersets(array $user)
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
    public function testGetIptrafficService(array $service)
    {
        parent::testGetIptrafficService($service);
    }

    /**
     * @depends testAddUser
     * @depends testAddIptrafficService
     * @depends testGetDiscountPeriods
     */
    public function testAddIptrafficServiceIpv6(array $user, array $service, array $discount_periods)
    {
        return parent::testAddIptrafficServiceIpv6($user, $service, $discount_periods);
    }

    /**
     * @depends testAddIptrafficServiceIpv6
     * @depends testGetDiscountPeriods
     */
    public function testGetIptrafficServiceIpv6(array $slink, array $discount_periods)
    {
        parent::testGetIptrafficServiceIpv6($slink, $discount_periods);
    }

    /**
     * @depends testAddIptrafficServiceIpv6
     */
    public function testSetRadiusAttr(array $slink)
    {
        parent::testSetRadiusAttr($slink);
    }

    /**
     * @depends testAddUser
     * @depends testAddIptrafficService
     * @depends testGetDiscountPeriods
     */
    public function testAddIptrafficServiceIpv6WithoutIp(array $user, array $service, array $discount_periods)
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
