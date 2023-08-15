<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Plugin\Sales\CustomerData;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\CustomerData\LastOrderedItems;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Plugin\Sales\CustomerData\CheckCatalogPermissionAfterLastOrderedItemsPlugin;
use PHPUnit\Framework\TestCase;

class CheckCatalogPermissionAfterLastOrderedItemsPluginTest extends TestCase
{
    /**
     * @var Index|MockObject
     */
    private $permissionIndexMock;

    /**
     * @var ConfigInterface|MockObject
     */
    private $permissionsConfigMock;

    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var CheckCatalogPermissionAfterLastOrderedItemsPlugin
     */
    private $plugin;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permissionIndexMock = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIndexForProduct'])
            ->getMockForAbstractClass();
        $this->permissionsConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            CheckCatalogPermissionAfterLastOrderedItemsPlugin::class,
            [
                'permissionIndex' => $this->permissionIndexMock,
                'permissionsConfig' => $this->permissionsConfigMock,
                'customerSession' => $this->customerSessionMock,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    /**
     * Test case for afterGetSectionData
     *
     * @param bool $isEnabled
     * @param int $customerGroupId
     * @param int $storeId
     * @param array $permissions
     * @param array $result
     * @param array $expected
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @dataProvider afterGetSectionDataProvider
     */
    public function testAfterGetSectionData(
        bool  $isEnabled,
        int   $customerGroupId,
        int   $storeId,
        array $permissions,
        array $result,
        array $expected
    ): void {
        /** @var LastOrderedItems|MockObject $storeMock */
        $subjectMock = $this->getMockBuilder(LastOrderedItems::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Store|MockObject $storeMock */
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')
            ->willReturn($storeId);
        $this->permissionsConfigMock->method('isEnabled')
            ->willReturn($isEnabled);
        $this->customerSessionMock->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
        $this->storeManagerMock->method('getStore')
            ->willReturn($storeMock);
        $this->permissionIndexMock->method('getIndexForProduct')
            ->willReturn($permissions);

        $actual = $this->plugin->afterGetSectionData($subjectMock, $result);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for afterGetSectionData
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function afterGetSectionDataProvider(): array
    {
        return [
            'when permission config is not enabled' => [
                false, 1, 1, [
                    1 => ['product_id' => '1',
                        'store_id' => '1',
                        'customer_group_id' => '1',
                        'grant_catalog_category_view' => '-1',
                        'grant_catalog_product_price' => '-1',
                        'grant_checkout_items' => '-1',
                        'index_id' => '15',
                    ]
                ],
                [
                    'items' => [
                        0 => [
                            'id' => 1,
                            'name' => 'product1',
                            'url' => 'http://test.com/product1',
                            'is_Salable' => true,
                            'product_id' => 1
                        ]
                    ]
                ],
                ['items' => [
                    0 => [
                        'id' => 1,
                        'name' => 'product1',
                        'url' => 'http://test.com/product1',
                        'is_Salable' => true,
                        'product_id' => 1
                    ]
                ]
                ]
            ],
            'when permission config is enabled and catalog view does have permissions' => [
                true, 1, 1, [
                    1 => [
                        'product_id' => '1',
                        'store_id' => '1',
                        'customer_group_id' => '1',
                        'grant_catalog_category_view' => '-1',
                        'grant_catalog_product_price' => '-1',
                        'grant_checkout_items' => '-1',
                        'index_id' => '15',
                    ]
                ],
                [
                    'items' => [
                        0 => [
                            'id' => 1,
                            'name' => 'product1',
                            'url' => 'http://test.com/product1',
                            'is_Salable' => true,
                            'product_id' => 1
                        ]
                    ]
                ],
                [
                    'items' => [
                        0 => [
                            'id' => 1,
                            'name' => 'product1',
                            'url' => 'http://test.com/product1',
                            'is_Salable' => true,
                            'product_id' => 1
                        ]
                    ]
                ]
            ],
            'when permission config is enabled and catalog view does not have permissions' => [
                true, 1, 1, [
                    1 => [
                        'product_id' => '1',
                        'store_id' => '1',
                        'customer_group_id' => '1',
                        'grant_catalog_category_view' => '-2',
                        'grant_catalog_product_price' => '-2',
                        'grant_checkout_items' => '-2',
                        'index_id' => '15',
                    ]
                ],
                [
                    'items' => [
                        0 => [
                            'id' => 1,
                            'name' => 'product1',
                            'url' => 'http://test.com/product1',
                            'is_Salable' => true,
                            'product_id' => 1
                        ]
                    ]
                ],
                ['items' => []]
            ]
        ];
    }
}
