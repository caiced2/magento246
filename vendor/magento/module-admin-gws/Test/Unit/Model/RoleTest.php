<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\AdminGws\Model\Role;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for AdminGws Role model
 */
class RoleTest extends TestCase
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeManagerMock->expects($this->any())
            ->method('getWebsites')
            ->willReturn([1 => 'website']);
        $storeManagerMock->expects($this->any())
            ->method('getStores')
            ->willReturn([1 => 'store']);
        $storeManagerMock->expects($this->any())
            ->method('getGroups')
            ->willReturn([1 => 'group']);
        $this->role = $this->objectManagerHelper->getObject(
            Role::class,
            [
                'storeManager' => $storeManagerMock,
            ]
        );
    }

    /**
     * Tests setAdminRole method
     *
     * @param $gwsRelevantWebsites
     * @param $gwsStores
     * @param $gwsStoreGroups
     * @param $gwsWebsites
     * @return void
     * @dataProvider adminRoleDataProvider
     */
    public function testSetAdminRole(
        $gwsRelevantWebsites,
        $gwsStores,
        $gwsStoreGroups,
        $gwsWebsites
    ): void {
        $adminRole = $this->objectManagerHelper->getObject(
            \Magento\Authorization\Model\Role::class,
            [
                'data' => [
                    'gws_relevant_websites' => $gwsRelevantWebsites,
                    'gws_stores' => $gwsStores,
                    'gws_store_groups' => $gwsStoreGroups,
                    'gws_websites' => $gwsWebsites,
                ]
            ]
        );
        $this->role->setAdminRole($adminRole);
        $this->assertIsArray($this->role->getStoreGroupIds());
        $this->assertIsArray($this->role->getWebsiteIds());
        $this->assertIsArray($this->role->getStoreIds());
        $this->assertIsArray($this->role->getRelevantWebsiteIds());
    }

    /**
     * Admin role data provider
     *
     * @return array
     */
    public function adminRoleDataProvider(): array
    {
        return [
            [null, null, null, null],
            [
                [1, 2, 3],
                [1, 2, 3],
                [1, 2, 3],
                [1, 2, 3],
            ],
        ];
    }

    /**
     * Tests hasExclusiveCategoryAccess method with null in category path
     *
     * @return void
     */
    public function testHasExclusiveCategoryAccessWithNullInCategoryPath(): void
    {
        $adminRole = $this->objectManagerHelper->getObject(
            \Magento\Authorization\Model\Role::class,
            [
                'data' => [
                    'gws_relevant_websites' => 1,
                    'gws_stores' => 2,
                    'gws_store_groups' => 3,
                ]
            ]
        );
        $this->role->setAdminRole($adminRole);
        $actual = $this->role->hasExclusiveCategoryAccess(null);
        $this->assertEquals(false, $actual);
    }
}
