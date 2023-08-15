<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\Staging;

use Magento\SalesRuleStaging\Model\Staging\PreviewStoreIdResolver;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test cart price rule staging preview store id resolver
 */
class PreviewStoreIdResolverTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var PreviewStoreIdResolver
     */
    private $model;

    /**
     * @var array
     */
    private $scopes = [
        'websites' => [
            'admin' => [
                'website_id' => '0',
                'code' => 'admin',
                'name' => 'Admin',
                'sort_order' => '0',
                'default_group_id' => '0',
                'is_default' => '0',
            ],
            'base' => [
                'website_id' => '1',
                'code' => 'base',
                'name' => 'Main Website',
                'sort_order' => '0',
                'default_group_id' => '1',
                'is_default' => '1',
            ],
            'web_ca' => [
                'website_id' => '2',
                'code' => 'web_ca',
                'name' => 'Canada',
                'sort_order' => '0',
                'default_group_id' => '4',
                'is_default' => '0',
            ],
            'web_eu' => [
                'website_id' => '3',
                'code' => 'web_eu',
                'name' => 'Europe',
                'sort_order' => '0',
                'default_group_id' => '3',
                'is_default' => '0',
            ],
        ],
        'groups' => [
            0 => [
                'group_id' => '0',
                'website_id' => '0',
                'name' => 'Default',
                'root_category_id' => '0',
                'default_store_id' => '0',
                'code' => 'default',
            ],
            1 => [
                'group_id' => '1',
                'website_id' => '1',
                'name' => 'Main Website Store',
                'root_category_id' => '2',
                'default_store_id' => '1',
                'code' => 'main_website_store',
            ],
            2 => [
                'group_id' => '2',
                'website_id' => '2',
                'name' => 'Western Canada',
                'root_category_id' => '2',
                'default_store_id' => '2',
                'code' => 'we_store_ca',
            ],
            3 => [
                'group_id' => '3',
                'website_id' => '3',
                'name' => 'Western Europe',
                'root_category_id' => '2',
                'default_store_id' => '3',
                'code' => 'we_eu',
            ],
            4 => [
                'group_id' => '4',
                'website_id' => '2',
                'name' => 'Northern Canada',
                'root_category_id' => '2',
                'default_store_id' => '4',
                'code' => 'nth_ca',
            ],
        ],
        'stores' => [
            'admin' => [
                'store_id' => '0',
                'code' => 'admin',
                'website_id' => '0',
                'group_id' => '0',
                'name' => 'Admin',
                'sort_order' => '0',
                'is_active' => '1',
            ],
            'default' => [
                'store_id' => '1',
                'code' => 'default',
                'website_id' => '1',
                'group_id' => '1',
                'name' => 'Default Store View',
                'sort_order' => '0',
                'is_active' => '1',
            ],
            'fr_ca' => [
                'store_id' => '2',
                'code' => 'fr_ca',
                'website_id' => '2',
                'group_id' => '2',
                'name' => 'French',
                'sort_order' => '0',
                'is_active' => '1',
            ],
            'de_eu' => [
                'store_id' => '3',
                'code' => 'de_eu',
                'website_id' => '3',
                'group_id' => '3',
                'name' => 'German',
                'sort_order' => '0',
                'is_active' => '1',
            ],
            'en_ca' => [
                'store_id' => '4',
                'code' => 'en_ca',
                'website_id' => '2',
                'group_id' => '4',
                'name' => 'English',
                'sort_order' => '0',
                'is_active' => '1',
            ],
        ],
    ];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->configureStores();
        $this->model = new PreviewStoreIdResolver(
            $this->storeManager
        );
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $websiteIds
     * @param int|null $storeId
     */
    public function testExecute(array $websiteIds, ?int $storeId): void
    {
        $this->assertEquals($storeId, $this->model->execute($websiteIds));
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                [1],
                1
            ],
            [
                [1,2],
                1
            ],
            [
                [1,2,3],
                1
            ],
            [
                [2],
                4
            ],
            [
                [2,3],
                4
            ],
            [
                [3],
                3
            ],
            [
                [],
                null
            ],
            [
                [4],
                null
            ]
        ];
    }

    private function configureStores(): void
    {
        $websites = [];
        $groups = [];
        $stores = [];
        foreach ($this->scopes['websites'] as $data) {
            $website = $this->getMockBuilder(Website::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
            $website->setIdFieldName('website_id');
            $website->setData($data);
            $websites[] = $website;
        }

        foreach ($this->scopes['groups'] as $data) {
            $group = $this->getMockBuilder(Group::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
            $group->setIdFieldName('group_id');
            $group->setData($data);
            $groups[] = $group;
        }

        foreach ($this->scopes['stores'] as $data) {
            $store = $this->getMockBuilder(Store::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
            $store->setIdFieldName('store_id');
            $store->setData($data);
            $stores[] = $store;
        }

        $this->storeManager->method('getWebsites')
            ->willReturn($websites);

        $this->storeManager->method('getGroups')
            ->willReturn($groups);

        $this->storeManager->method('getStores')
            ->willReturn($stores);
    }
}
