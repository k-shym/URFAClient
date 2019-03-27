<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Tests\TestHelperUtil;
use UrfaClient\Config\UrfaConfig;

/**
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 */
class UrfaConfigTest extends TestCase
{
    private $urfaConfig;
    private $helper;

    /**
     *
     */
    public function setUp(): void
    {
        $this->urfaConfig = new UrfaConfig();
        $this->helper = new TestHelperUtil();
    }

    public function testApiFileExist()
    {
        $this->assertFileExists($this->urfaConfig->getApi());
    }

    /**
     * @return array
     */
    public function propertiesWithGettersAndSetters()
    {
        return [
            'admin' => ['admin', 'isAdmin', 'setAdmin'],
            'log' => ['log', 'useLog', 'setLog'],
            'cache' => ['cache', 'useCache', 'setCache'],
            'cacheTime' => ['cacheTime', 'getCacheTime', 'setCacheTime'],
            'login' => ['login', 'getLogin', 'setLogin'],
            'password' => ['password', 'getPassword', 'setPassword'],
            'host' => ['host', 'getHost', 'setHost'],
            'port' => ['port', 'getPort', 'setPort'],
            'timeout' => ['timeout', 'getTimeout', 'setTimeout'],
            'protocol' => ['protocol', 'getProtocol', 'setProtocol'],
            'session' => ['session', 'getSession', 'setSession'],
            'api' => ['api', 'getApi', 'setApi'],
        ];
    }

    /**
     * @dataProvider propertiesWithGettersAndSetters
     * @param $property
     * @param $getterFunction
     * @param $setterFunction
     */
    public function testTakeGetterAndSetterName($property, $getterFunction, $setterFunction): void
    {
        [$getterFunctionGenerated, $setterFunctionGenerated] = $this->helper->invokeMethod($this->urfaConfig, 'takeGetterAndSetterName', [$property]);
        $this->assertEquals($getterFunction, $getterFunctionGenerated);
        $this->assertEquals($setterFunction, $setterFunctionGenerated);
    }

    /**
     * @dataProvider propertiesWithGettersAndSetters
     * @param $property
     */
    public function testPropertyExist($property): void
    {
        $this->assertClassHasAttribute($property, UrfaConfig::class);
    }


    public function testPropertiesNonExist(): void
    {
        $this->assertClassNotHasAttribute('nonExist', UrfaConfig::class);
    }

    /**
     * @dataProvider propertiesWithGettersAndSetters
     * @param $property
     * @param $getterFunction
     * @param $setterFunction
     */
    public function testMethodExist($property, $getterFunction, $setterFunction): void
    {
        $this->assertTrue(method_exists($this->urfaConfig, $getterFunction));
        $this->assertTrue(method_exists($this->urfaConfig, $setterFunction));
    }


    public function testMethodNonExist(): void
    {
        $this->assertFalse(method_exists($this->urfaConfig, 'getNonExist'));
        $this->assertFalse(method_exists($this->urfaConfig, 'setNonExist'));
    }


    public function testLogin()
    {

        $this->assertEquals($this->urfaConfig->getLogin(), 'init');
        $this->urfaConfig->setLogin('login');
        $this->assertEquals($this->urfaConfig->getLogin(), 'login');
    }

    public function testPassword()
    {
        $this->assertEquals($this->urfaConfig->getPassword(), 'init');
        $this->urfaConfig->setPassword('pass');
        $this->assertEquals($this->urfaConfig->getPassword(), 'pass');
    }

    public function testUpdateOptions()
    {
        $this->assertEquals($this->urfaConfig->getLogin(), 'init');
        $this->assertEquals($this->urfaConfig->getPassword(), 'init');
        $this->assertEquals($this->urfaConfig->getPort(), 11758);
        $this->assertEquals($this->urfaConfig->getTimeout(), 30);

        $this->assertEquals($this->urfaConfig->getHost(), 'localhost');
        $this->assertEquals($this->urfaConfig->getProtocol(), 'auto');
        $this->assertNotEmpty($this->urfaConfig->getApi());
        $this->assertEmpty($this->urfaConfig->getSession());

        $this->assertFalse($this->urfaConfig->isAdmin());
        $this->assertFalse($this->urfaConfig->useCache());
        $this->assertEquals($this->urfaConfig->getCacheTime(), 60);
        $this->assertFalse($this->urfaConfig->useLog());

        $options = [
            'login' => 'login',
            'password' => 'pass',
            'cache' => 'true',
            'log' => true,
            'admin' => true,
            'port' => 4444,
            'timeout' => 120,
            'host' => '192.168.0.1',
            'protocol' => 'tls',
            'cacheTime' => 300,
            'session' => 'a1c00e836b7c7dcc0856897c8e8614d172df641c',
            'api' => '/netup/api/api_53-005.xml',
        ];
        $this->urfaConfig->updateOptions($options);

        $this->assertEquals($this->urfaConfig->getLogin(), 'login');
        $this->assertEquals($this->urfaConfig->getPassword(), 'pass');
        $this->assertEquals($this->urfaConfig->getPort(), 4444);
        $this->assertEquals($this->urfaConfig->getTimeout(), 120);
        $this->assertEquals($this->urfaConfig->getHost(), '192.168.0.1');
        $this->assertEquals($this->urfaConfig->getProtocol(), 'tls');
        $this->assertEquals($this->urfaConfig->getSession(), 'a1c00e836b7c7dcc0856897c8e8614d172df641c');
        $this->assertEquals($this->urfaConfig->getApi(), '/netup/api/api_53-005.xml');

        $this->assertTrue($this->urfaConfig->isAdmin());
        $this->assertTrue($this->urfaConfig->useCache());
        $this->assertEquals($this->urfaConfig->getCacheTime(), 300);
        $this->assertTrue($this->urfaConfig->useLog());
    }

    /**
     * @dataProvider protocolVariants
     */
    public function testProtocolVariants($expected, $actual)
    {
        $this->assertEquals($this->urfaConfig->getProtocol(), 'auto');
        $this->urfaConfig->setProtocol($expected);
        $this->assertEquals($this->urfaConfig->getProtocol(), $actual);
    }

    public function protocolVariants()
    {
        return [
            'auto' => ['auto', 'auto'],
            'tls' => ['tls', 'tls'],
            'ssl' => ['ssl', 'ssl'],
            'nonExist' => ['nonExist', 'auto'],
        ];
    }
}
