<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Plugin\Theme\Block\Html;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Plugin\Theme\Block\Html\Topmenu;
use Magento\Customer\Model\Session\Storage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TopmenuTest extends TestCase
{
    /**
     * @var Topmenu
     */
    private $topmenuPlugin;

    /**
     * @var ConfigInterface|MockObject
     */
    private $catalogPermissionsConfigMock;

    /**
     * @var Storage|MockObject
     */
    private $customerSessionStorageMock;

    /**
     * @var \Magento\Theme\Block\Html\Topmenu|MockObject
     */
    private $topmenuMock;

    /**
     * @var array
     */
    private $baseResult = [
        'key' => 'value',
        'another key' => 'another value'
    ];

    protected function setUp(): void
    {
        $this->catalogPermissionsConfigMock = $this->getMockForAbstractClass(
            ConfigInterface::class
        );
        $this->customerSessionStorageMock = $this->getMockBuilder(Storage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMock();
        $this->topmenuMock = $this->getMockBuilder(\Magento\Theme\Block\Html\Topmenu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->topmenuPlugin = new Topmenu(
            $this->catalogPermissionsConfigMock,
            $this->customerSessionStorageMock
        );
    }

    /**
     * @param bool $catalogPermissionsEnabled
     * @param int $getCustomerGroupIdCallCount
     * @param array $expectedResult
     *
     * @dataProvider afterGetCacheKeyInfoDataProvider
     */
    public function testAfterGetCacheKeyInfo(
        bool $catalogPermissionsEnabled,
        int $getCustomerGroupIdCallCount,
        array $expectedResult
    ) {
        $this->catalogPermissionsConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn($catalogPermissionsEnabled);
        $this->customerSessionStorageMock->expects($this->exactly($getCustomerGroupIdCallCount))
            ->method('getCustomerGroupId')
            ->willReturn('customerGroupId');

        $cacheKeyInfo = $this->topmenuPlugin->afterGetCacheKeyInfo($this->topmenuMock, $this->baseResult);
        $this->assertEquals($cacheKeyInfo, $expectedResult);
    }

    public function afterGetCacheKeyInfoDataProvider()
    {
        return [
            'Catalog Permissions Enabled' => [
                true,
                1,
                array_merge($this->baseResult, ['customer_group_id' => 'customerGroupId'])
            ],
            'Catalog Permissions Disabled' => [
                false,
                0,
                $this->baseResult
            ]
        ];
    }
}
