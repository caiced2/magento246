<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\TargetRule\Helper\Data;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\TargetRule\Helper\Data
 */
class DataTest extends TestCase
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock
            ->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->data = new Data(
            $this->contextMock
        );
    }

    /**
     * @param int $type
     * @param string $configPath
     * @param int $result
     * @return void
     * @dataProvider getMaximumNumberOfProductDataProvider
     */
    public function testGetMaximumNumberOfProduct($type, $configPath, $result)
    {
        $this->scopeConfigMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($configPath, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->data->getMaximumNumberOfProduct($type)
        );
    }

    /**
     * @return array
     */
    public function getMaximumNumberOfProductDataProvider()
    {
        return [
            [Rule::RELATED_PRODUCTS, Data::XML_PATH_TARGETRULE_CONFIG . 'related_position_limit', 2],
            [Rule::UP_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'upsell_position_limit', 4],
            [Rule::CROSS_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'crosssell_position_limit', 8]
        ];
    }

    /**
     * @return void
     */
    public function testGetMaximumNumberOfProductException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->data->getMaximumNumberOfProduct(-123);
    }

    /**
     * @param int $type
     * @param string $configPath
     * @param int $result
     * @return void
     * @dataProvider getShowProductsDataProvider
     */
    public function testGetShowProducts($type, $configPath, $result)
    {
        $this->scopeConfigMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($configPath, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->data->getShowProducts($type)
        );
    }

    /**
     * @return void
     */
    public function testGetShowProductsException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->data->getShowProducts(-123);
    }

    /**
     * @return array
     */
    public function getShowProductsDataProvider()
    {
        return [
            [Rule::RELATED_PRODUCTS, Data::XML_PATH_TARGETRULE_CONFIG . 'related_position_behavior', 1],
            [Rule::UP_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'upsell_position_behavior', 3],
            [Rule::CROSS_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'crosssell_position_behavior', 7]
        ];
    }

    /**
     * @return void
     */
    public function testGetMaxProductsListResult()
    {
        $this->assertEquals(Data::MAX_PRODUCT_LIST_RESULT, $this->data->getMaxProductsListResult(100500));
    }

    /**
     * @param int $type
     * @param string $configPath
     * @param int $result
     * @return void
     * @dataProvider getRotationModeDataProvider
     */
    public function testGetRotationMode($type, $configPath, $result)
    {
        $this->scopeConfigMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($configPath, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($result);

        $this->assertEquals(
            $result,
            $this->data->getRotationMode($type)
        );
    }

    /**
     * @return void
     */
    public function testGetRotationModeException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->data->getRotationMode(-12345);
    }

    /**
     * @return array
     */
    public function getRotationModeDataProvider()
    {
        return [
            [Rule::RELATED_PRODUCTS, Data::XML_PATH_TARGETRULE_CONFIG . 'related_rotation_mode', 3],
            [Rule::UP_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'upsell_rotation_mode', 5],
            [Rule::CROSS_SELLS, Data::XML_PATH_TARGETRULE_CONFIG . 'crosssell_rotation_mode', 9]
        ];
    }
}
