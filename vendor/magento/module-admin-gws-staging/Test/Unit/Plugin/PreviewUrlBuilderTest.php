<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGwsStaging\Test\Unit\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\AdminGwsStaging\Plugin\PreviewUrlBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test staging preview URL for user with different website restriction
 */
class PreviewUrlBuilderTest extends TestCase
{
    /**
     * @var PreviewUrlBuilder
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $role = $this->createMock(Role::class);
        $role->method('getStoreGroupIds')
            ->willReturn(
                [
                    2,
                    5
                ]
            );
        $storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $storeManager->method('getGroup')
            ->willReturnCallback(
                function () {
                    $group = $this->getMockForAbstractClass(GroupInterface::class);
                    $group->method('getDefaultStoreId')->willReturn(11);
                    return $group;
                }
            );
        $storeManager->method('getStore')
            ->willReturnCallback(
                function () {
                    $store = $this->getMockForAbstractClass(StoreInterface::class);
                    $store->method('getCode')->willReturn('spanish');
                    return $store;
                }
            );
        $this->model = $objectManager->getObject(
            PreviewUrlBuilder::class,
            [
                'role' => $role,
                'storeManager' => $storeManager
            ]
        );
    }

    /**
     * Test that the first store is used if store is not explicitly requested
     *
     * @param string|null $actualStore
     * @param string|null $expectedStore
     * @dataProvider provideBeforeGetPreviewUrl
     */
    public function testBeforeGetPreviewUrl(?string $actualStore, ?string $expectedStore)
    {
        $urlBuilder = $this->createMock(UrlBuilder::class);
        $versionId = 7434532322;
        $url = 'catalog/product/view';
        $this->assertEquals(
            [
                $versionId,
                $url,
                $expectedStore
            ],
            $this->model->beforeGetPreviewUrl($urlBuilder, $versionId, $url, $actualStore)
        );
    }

    /**
     * @return array
     */
    public function provideBeforeGetPreviewUrl(): array
    {
        return [
            [
                null,
                'spanish'
            ],
            [
                'french',
                'french'
            ],
        ];
    }
}
