<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Helper\Data;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var Data
     */
    protected $subject;

    /**
     * @var MockObject
     */
    protected $websiteMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = Data::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        /** @var Context $context */
        $context = $arguments['context'];
        $this->storeManagerMock = $arguments['storeManager'];
        $this->scopeConfigMock = $context->getScopeConfig();
        $this->websiteMock = $this->createMock(Website::class);

        $this->subject = $objectManagerHelper->getObject($className, $arguments);
    }

    public function testIsEnabled()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(Data::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->assertTrue($this->subject->isEnabled());
    }

    public function testGetConfigValue()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = 'section';
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getConfigValue($section, $field, $websiteId));
    }

    public function testGetGeneralConfig()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = Data::XML_PATH_SECTION_GENERAL;
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getGeneralConfig($field, $websiteId));
    }

    public function testGetPointsConfig()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = Data::XML_PATH_SECTION_POINTS;
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getPointsConfig($field, $websiteId));
    }

    public function testGetNotificationConfig()
    {
        $websiteId = 2;
        $code = 'website_code';
        $configValue = 'config_value';
        $section = Data::XML_PATH_SECTION_NOTIFICATIONS;
        $field = 'field';

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($this->websiteMock);
        $this->websiteMock->expects($this->once())->method('getCode')->willReturn($code);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($section . $field, 'website', $code)
            ->willReturn($configValue);

        $this->assertEquals($configValue, $this->subject->getNotificationConfig($field, $websiteId));
    }

    /**
     * @param int $points
     * @param string $expectedResult
     *
     * @dataProvider formatPointsDeltaDataProvider
     */
    public function testFormatPointsDelta($points, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->subject->formatPointsDelta($points));
    }

    /**
     * @return array
     */
    public function formatPointsDeltaDataProvider()
    {
        return [
            ['points' => -100, 'expectedResult' => '-100'],
            ['points' => 100, 'expectedResult' => '+100'],
        ];
    }
}
