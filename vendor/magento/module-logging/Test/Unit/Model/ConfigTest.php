<?php declare(strict_types=1);
/**
 * Test \Magento\Logging\Model\Config
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Model\Config;
use Magento\Logging\Model\Config\Data;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var MockObject|Data
     */
    private $storageMock;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->storageMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggingConfig = [
            'actions' => [
                'test_action_withlabel' => ['label' => 'Test Action Label'],
                'test_action_withoutlabel' => [],
            ],
            'test' => ['label' => 'Test Label'],
            'configured_log_group' => [
                'label' => 'Log Group With Configuration',
                'actions' => [
                    'adminhtml_checkout_index' => [
                        'log_name' => 'configured_log_group',
                        'action' => 'view',
                        'expected_models' => [AbstractModel::class => []],
                    ],
                ],
            ],
        ];
        $this->storageMock->expects(
            $this->once()
        )->method(
            'get'
        )->with(
            'logging'
        )->willReturn(
            $loggingConfig
        );
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $objectManager->getObject(
            Config::class,
            [
                'dataStorage' => $this->storageMock,
                'scopeConfig' => $this->scopeConfigMock,
                'serializer' => $this->serializerMock,
            ]
        );
    }

    public function testLabels()
    {
        $expected = ['test' => 'Test Label', 'configured_log_group' => 'Log Group With Configuration'];
        $result = $this->config->getLabels();
        $this->assertEquals($expected, $result);
    }

    public function testGetActionLabel()
    {
        $expected = 'Test Action Label';
        $result = $this->config->getActionLabel('test_action_withlabel');
        $this->assertEquals($expected, $result);
    }

    public function testGetActionWithoutLabel()
    {
        $this->assertEquals('test_action_withoutlabel', $this->config->getActionLabel('test_action_withoutlabel'));
        $this->assertEquals('nonconfigured_action', $this->config->getActionLabel('nonconfigured_action'));
    }

    public function testGetSystemConfigValues()
    {
        $config = ['enterprise_checkout' => 1, 'customer' => 1];
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', ScopeInterface::SCOPE_STORE)
            ->willReturn('serializedConfig');
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serializedConfig')
            ->willReturn($config);
        $this->assertEquals($config, $this->config->getSystemConfigValues());
    }

    public function testGetSystemConfigValuesNegative()
    {
        $expected = ['test' => 1, 'configured_log_group' => 1];
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->serializerMock->expects($this->never())
            ->method('unserialize');
        $this->assertEquals($expected, $this->config->getSystemConfigValues());
    }

    public function testGetSystemConfigValuesNegativeWithException()
    {
        $expected = ['test' => 1, 'configured_log_group' => 1];
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', ScopeInterface::SCOPE_STORE)
            ->willReturn('{"key":');
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willThrowException(new \Exception());

        $this->assertEquals($expected, $this->config->getSystemConfigValues());
    }

    public function testHasSystemConfigValues()
    {
        $config = ['enterprise_checkout' => 1, 'customer' => 1];

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', ScopeInterface::SCOPE_STORE)
            ->willReturn('serializedConfig');
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serializedConfig')
            ->willReturn($config);
        $this->assertTrue($this->config->hasSystemConfigValue('enterprise_checkout'));
        $this->assertFalse($this->config->hasSystemConfigValue('enterprise_catalogevent'));
    }

    public function testIsEventGroupLogged()
    {
        $config = ['enterprise_checkout' => 1, 'customer' => 1];

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', ScopeInterface::SCOPE_STORE)
            ->willReturn('serializedConfig');

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serializedConfig')
            ->willReturn($config);

        $this->assertTrue($this->config->isEventGroupLogged('enterprise_checkout'));
        $this->assertFalse($this->config->isEventGroupLogged('enterprise_catalogevent'));
    }

    public function testGetEventByFullActionName()
    {
        $expected = [
            'log_name' => 'configured_log_group',
            'action' => 'view',
            'expected_models' => [AbstractModel::class => []],
        ];
        $this->assertEquals($expected, $this->config->getEventByFullActionName('adminhtml_checkout_index'));
    }
}
