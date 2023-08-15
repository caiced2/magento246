<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Plugin\Catalog\Controller\Adminhtml\Product\Initialization;

use Magento\AdminGws\Model\Role;
use Magento\AdminGws\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\GalleryUpdater;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for update product gallery data
 */
class GalleryUpdaterTest extends TestCase
{
    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var Product
     */
    private $subject;

    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * @var GalleryUpdater
     */
    private $plugin;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->roleMock = $this->createMock(Role::class);
        $this->subject = $this->createMock(Product::class);
        $this->store = $this->getMockForAbstractClass(StoreInterface::class);
        $this->plugin = $objectManager->getObject(GalleryUpdater::class, ['role' => $this->roleMock]);
    }

    /**
     * Verify that gallery data has been set for the product after data initialization
     *
     * @param array $productData
     * @dataProvider afterInitializeFromDataDataProvider
     */
    public function testAfterInitializeFromData(array $productData): void
    {
        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->willReturn(true);

        /** @var Helper|MockObject $helperMock */
        $helperMock = $this->createMock(Helper::class);

        $this->subject->expects($this->any())
            ->method('isLockedAttribute')
            ->with('media_gallery')
            ->willReturn(true);

        $this->subject->expects($this->any())
            ->method('getData')
            ->with('media_gallery')
            ->willReturn([
                'images' => [
                    [
                        'label' => 'label',
                        'disabled' => 0,
                    ],
                ]
            ]);

        $this->store->method('getId')->willReturn(1);

        $this->subject->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);

        $this->subject->expects($this->once())
            ->method('unlockAttribute')
            ->with('media_gallery');

        $this->subject->expects($this->once())
            ->method('setData')
            ->with('media_gallery');

        $this->subject->expects($this->once())
            ->method('lockAttribute')
            ->with('media_gallery');

        $this->plugin->afterInitializeFromData($helperMock, $this->subject, $this->subject, $productData);
    }

    /**
     * Data provider for testAfterInitializeFromData
     *
     * @return array
     */
    public function afterInitializeFromDataDataProvider() : array
    {
        return [
            [
                'productData' => [
                    'media_gallery' => [
                        'images' => [
                            [
                                'label' => 'label_changed',
                                'disabled' => 1,
                            ],
                        ]
                    ]
                ],
            ],
        ];
    }
}
