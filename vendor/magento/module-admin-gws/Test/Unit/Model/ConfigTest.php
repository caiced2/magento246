<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\AdminGws\Model\Config;
use Magento\AdminGws\Model\Config\Reader;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Reader|MockObject
     */
    protected $_readerMock;

    /**
     * @var ScopeInterface|MockObject
     */
    protected $_configScopeMock;

    /**
     * @var CacheInterface|MockObject
     */
    protected $_cacheMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var Config
     */
    protected $_model;

    protected function setUp(): void
    {
        $cacheId = null;
        $this->_readerMock = $this->createMock(Reader::class);
        $this->_configScopeMock = $this->getMockForAbstractClass(ScopeInterface::class);
        $this->_cacheMock = $this->getMockForAbstractClass(CacheInterface::class);
        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);

        $this->_model = new Config(
            $this->_readerMock,
            $this->_configScopeMock,
            $this->_cacheMock,
            $cacheId,
            $this->serializerMock
        );
    }

    /**
     * @dataProvider getCallbacksDataProvider
     */
    public function testGetCallbacks($value, $expected)
    {
        $this->_cacheMock->expects($this->any())
            ->method('load')
            ->willReturn('serailizedData');

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serailizedData')
            ->willReturn($value);

        $this->assertEquals($expected, $this->_model->getCallbacks('group'));
    }

    public function getCallbacksDataProvider()
    {
        return [
            'generic_key_exist' => [['callbacks' => ['group' => 'value']], 'value'],
            'return_default_value' => [['key_one' => 'value'], []]
        ];
    }

    /**
     * @dataProvider getDeniedAclResourcesDataProvider
     */
    public function testGetDeniedAclResources($value, $expected)
    {
        $this->_cacheMock->expects($this->any())
            ->method('load')
            ->willReturn(json_encode($value));

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($value);

        $this->assertEquals($expected, $this->_model->getDeniedAclResources('level'));
    }

    public function getDeniedAclResourcesDataProvider()
    {
        return [
            'generic_key_exist' => [['acl' => ['level' => 'value']], 'value'],
            'return_default_value' => [['key_one' => 'value'], []]
        ];
    }
}
