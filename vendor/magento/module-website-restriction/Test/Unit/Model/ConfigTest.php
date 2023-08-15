<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\WebsiteRestriction\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var MockObject
     */
    protected $_cacheMock;

    /**
     * @var MockObject
     */
    protected $_scopeConfigMock;

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
        $this->objectManager = new ObjectManager($this);
        $this->_cacheMock = $this->getMockForAbstractClass(CacheInterface::class);
        $this->_scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $cacheId = null;

        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);

        $this->_model = $this->objectManager->getObject(
            Config::class,
            [
                'cache' => $this->_cacheMock,
                'scopeConfig' => $this->_scopeConfigMock,
                $cacheId,
                'serializer' => $this->serializerMock
            ]
        );
    }

    /**
     * @dataProvider getGenericActionsDataProvider
     */
    public function testGetGenericActions($value, $expected)
    {
        $this->_cacheMock->expects($this->any())
            ->method('load')
            ->willReturn('serializedData');

        $this->serializerMock->expects($this->exactly(2))
            ->method('unserialize')
            ->with('serializedData')
            ->willReturn($value);

        $this->assertEquals($expected, $this->_model->getGenericActions());
    }

    public function getGenericActionsDataProvider()
    {
        return [
            'generic_key_exist' => [['generic' => 'value'], 'value'],
            'return_default_value' => [['key_one' => 'value'], []]
        ];
    }

    /**
     * @dataProvider getRegisterActionsDataProvider
     */
    public function testGetRegisterActions($value, $expected)
    {
        $this->_cacheMock->expects($this->any())
            ->method('load')
            ->willReturn('serializedData');

        $this->serializerMock->expects($this->exactly(2))
            ->method('unserialize')
            ->with('serializedData')
            ->willReturn($value);

        $this->assertEquals($expected, $this->_model->getRegisterActions());
    }

    public function getRegisterActionsDataProvider()
    {
        return [
            'register_key_exist' => [['register' => 'value'], 'value'],
            'return_default_value' => [['key_one' => 'value'], []]
        ];
    }

    public function testIsRestrictionEnabled()
    {
        $store = null;
        $this->_scopeConfigMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            'general/restriction/is_active',
            ScopeInterface::SCOPE_STORE,
            $store
        )->willReturn(
            false
        );

        $this->assertFalse($this->_model->isRestrictionEnabled($store));
    }

    public function testGetMode()
    {
        $this->_scopeConfigMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            'general/restriction/mode',
            ScopeInterface::SCOPE_STORE
        )->willReturn(
            false
        );
        $this->assertEquals(0, $this->_model->getMode());
    }

    public function testGetHTTPStatusCode()
    {
        $this->_scopeConfigMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            'general/restriction/http_status'
        )->willReturn(
            false
        );
        $this->assertEquals(0, $this->_model->getHTTPStatusCode());
    }

    public function testGetHTTPRedirectCode()
    {
        $this->_scopeConfigMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            'general/restriction/http_redirect',
            ScopeInterface::SCOPE_STORE
        )->willReturn(
            true
        );
        $this->assertEquals(1, $this->_model->getHTTPRedirectCode());
    }

    public function testGetLandingPageCode()
    {
        $this->_scopeConfigMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            'general/restriction/cms_page',
            ScopeInterface::SCOPE_STORE
        )->willReturn(
            'config'
        );
        $this->assertEquals('config', $this->_model->getLandingPageCode());
    }
}
