<?php

namespace Tests\Unit\Log;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use UrfaClient\Log\LoggerWrapper;

/**
 * @author Siomkin Alexander <siomkin.alexander@gmail.com>
 */
class LoggerWrapperTest extends TestCase
{

    public function testConstruct()
    {
        $logMock = $this->getLogMock();

        $logger = new LoggerWrapper($logMock);

        $this->assertNotNull($logger);
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $logger);
    }

    /**
     * @dataProvider invalidLoggerDataProvider
     * @param mixed $source
     */
    public function testInvalidConstruct($source)
    {
        $this->expectException('\Psr\Log\InvalidArgumentException');
        new LoggerWrapper($source);
    }

    /**
     * @return array
     */
    public function invalidLoggerDataProvider()
    {
        return array(
            array(new \stdClass()),
            array(true),
            array(false),
            array(array()),
            array(1),
            array('test'),
        );
    }

    /**
     * @dataProvider validLoggerDataProvider
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function testLog($level, $message, $context)
    {

        $logMock = $this->getLogMock();
        $instance = new LoggerWrapper($logMock);
        $instance->log($level, $message, $context);
        $expected = array($level, $message, $context);
        $this->assertEquals($expected, $instance->getLastLog());
    }

    /**
     * @dataProvider validLoggerDataProvider
     * @param string $notUsed
     * @param string $message
     * @param array $context
     */
    public function testLogMethods($notUsed, $message, $context)
    {
        $methodsMap = array(
            LogLevel::EMERGENCY => 'emergency',
            LogLevel::ALERT => 'alert',
            LogLevel::CRITICAL => 'critical',
            LogLevel::ERROR => 'error',
            LogLevel::WARNING => 'warning',
            LogLevel::NOTICE => 'notice',
            LogLevel::INFO => 'info',
            LogLevel::DEBUG => 'debug',
        );
        $logMock = $this->getLogMock();
        $instance = new LoggerWrapper($logMock);
        foreach ($methodsMap as $level => $method) {
            $instance->{$method} ($message, $context);
            $expected = array($level, $message, $context);
            $this->assertEquals($expected, $instance->getLastLog());
        }
    }

    public function validLoggerDataProvider()
    {
        return array(
            array(LogLevel::EMERGENCY, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
            array(LogLevel::ALERT, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
            array(LogLevel::CRITICAL, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
            array(LogLevel::ERROR, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
            array(LogLevel::WARNING, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
            array(LogLevel::NOTICE, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
            array(LogLevel::INFO, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
            array(LogLevel::DEBUG, random_bytes(random_int(10, 20)), array(random_bytes(random_int(10, 20)))),
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \ReflectionException
     */
    public function getLogMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        $logMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMockForAbstractClass();

        return $logMock;
    }
}