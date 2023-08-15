<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\AdminGws\Plugin\StoreFilter;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StoreFilterTest extends TestCase
{
    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var StoreFilter
     */
    private $roleStoreManager;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var StoreInterface|MockObject
     */

    /**
     * Class dependencies initialization
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->roleStoreManager = $objectManager->getObject(
            StoreFilter::class,
            [
                'role' => $this->roleMock,
            ]
        );
    }

    /**
     * @param bool $isAll
     * @param int[] $roleStoreIds
     * @param int[] $stores
     * @dataProvider storesDataProvider
     */
    public function testGetStores($isAll, $roleStoreIds, $stores)
    {
        $this->roleMock->expects($this->once())
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->exactly($isAll ? 0 : 1))
            ->method('getStoreIds')
            ->willReturn($roleStoreIds);
        $expected = $stores;
        $this->assertEquals($expected, $this->roleStoreManager->afterGetStores($this->storeManagerMock, $stores));
    }

    public function storesDataProvider() : array
    {
        $storeMockA = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        $storeMockA->method('getId')->willReturn(1);
        $storeMockB = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        $storeMockB->method('getId')->willReturn(2);

        return [
            'isAll' => [true, [1, 2], [$storeMockA, $storeMockB]],
            'A & B' => [false, [1, 2], [$storeMockA, $storeMockB]],
            'A' => [false, [1], [$storeMockA]],
        ];
    }

    /**
     * @param bool $isAll
     * @param int[] $roleWebsiteIds
     * @param int[] $websites
     * @dataProvider websitesDataProvider
     */
    public function testGetWebsites($isAll, $roleWebsiteIds, $websites)
    {
        $this->roleMock->expects($this->once())
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->exactly($isAll ? 0 : 1))
            ->method('getRelevantWebsiteIds')
            ->willReturn($roleWebsiteIds);
        $expected = $websites;
        $this->assertEquals($expected, $this->roleStoreManager->afterGetWebsites($this->storeManagerMock, $websites));
    }

    public function websitesDataProvider() : array
    {
        $websiteMockA = $this->getMockBuilder(WebsiteInterface::class)
            ->getMockForAbstractClass();
        $websiteMockA->method('getId')->willReturn(1);
        $websiteMockB = $this->getMockBuilder(WebsiteInterface::class)
            ->getMockForAbstractClass();
        $websiteMockB->method('getId')->willReturn(2);

        return [
            'isAll' => [true, [1, 2], [$websiteMockA, $websiteMockB]],
            'A & B' => [false, [1, 2], [$websiteMockA, $websiteMockB]],
            'A' => [false, [1], [$websiteMockA]],
        ];
    }

    /**
     * @param bool $isAll
     * @param int[] $roleGroupIds
     * @param int[] $groups
     * @dataProvider groupsDataProvider
     */
    public function testGetGroups($isAll, $roleGroupIds, $groups)
    {
        $this->roleMock->expects($this->once())
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->exactly($isAll ? 0 : 1))
            ->method('getStoreGroupIds')
            ->willReturn($roleGroupIds);
        $expected = $groups;
        $this->assertEquals($expected, $this->roleStoreManager->afterGetGroups($this->storeManagerMock, $groups));
    }

    public function groupsDataProvider() : array
    {
        $groupMockA = $this->getMockBuilder(GroupInterface::class)
            ->getMockForAbstractClass();
        $groupMockA->method('getId')->willReturn(1);
        $groupMockB = $this->getMockBuilder(GroupInterface::class)
            ->getMockForAbstractClass();
        $groupMockB->method('getId')->willReturn(2);

        return [
            'isAll' => [true, [1, 2], [$groupMockA, $groupMockB]],
            'A & B' => [false, [1, 2], [$groupMockA, $groupMockB]],
            'A' => [false, [1], [$groupMockA]],
        ];
    }

    /**
     * @param bool $isAll
     * @param GroupInterface|null $defaultStore
     * @param int[] $storeIds
     * @param GroupInterface|null $expected
     *
     * @dataProvider getDefaultStoreViewDataProvider
     * @throws NoSuchEntityException
     */
    public function testGetDefaultStoreView($isAll, $defaultStore, $storeIds, $expected)
    {
        $this->roleMock->expects($this->once())
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->any())
            ->method('getStoreIds')
            ->willReturn($storeIds);

        if (!$isAll) {
            $this->storeManagerMock->expects($this->any())
                ->method('getStore')
                ->with(current($storeIds))
                ->willReturn($expected);
        }

        $this->assertEquals(
            $expected,
            $this->roleStoreManager->afterGetDefaultStoreView($this->storeManagerMock, $defaultStore)
        );
    }

    public function getDefaultStoreViewDataProvider() : array
    {
        $storeMockA = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        $storeMockA->method('getId')->willReturn(1);
        $storeMockB = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        $storeMockB->method('getId')->willReturn(3);
        $storeMockC = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        $storeMockC->method('getId')->willReturn(2);

        return [
            [true, $storeMockA, null, $storeMockA],
            [false, null, [1, 77, 777], $storeMockA],
            [false, $storeMockA, [1, 77, 777], $storeMockA],
            [false, $storeMockB, [2, 77, 777], $storeMockC]
        ];
    }
}
