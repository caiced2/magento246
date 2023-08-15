<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\ResourceModel\Product\Collection;

use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation;
use Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Collection\ProductLimitation as ProductLimitationPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLimitationTest extends TestCase
{
    /**
     * @var ProductLimitationPlugin
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $versionManagerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $objectManager->getObject(
            ProductLimitationPlugin::class,
            [
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    public function testAfterIsUsingPriceIndex()
    {
        $collectionMock = $this->getMockBuilder(ProductLimitation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->assertFalse($this->model->afterIsUsingPriceIndex($collectionMock, true));
    }
}
