<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\CatalogStaging\Model\Product\UpdateScheduler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * UpdateScheduler unit test.
 */
class UpdateSchedulerTest extends TestCase
{
    /**
     * @var Service|MockObject
     */
    private $updateService;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var ProductStagingInterface|MockObject
     */
    private $productStaging;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var UpdateScheduler
     */
    private $updateScheduler;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->updateService = $this->getMockBuilder(
            Service::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->versionManager = $this->getMockBuilder(
            VersionManager::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->productStaging = $this->getMockBuilder(
            ProductStagingInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this->getMockBuilder(
            ProductRepositoryInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->updateScheduler = $objectManager->getObject(
            UpdateScheduler::class,
            [
                'updateService' => $this->updateService,
                'versionManager' => $this->versionManager,
                'productStaging' => $this->productStaging,
                'productRepository' => $this->productRepository,
            ]
        );
    }

    /**
     * Test schedule method.
     */
    public function testSchedule()
    {
        $update = $this->getMockBuilder(
            UpdateInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->updateService->expects($this->exactly(1))->method('createUpdate')->willReturn($update);
        $this->versionManager->expects($this->exactly(1))->method('setCurrentVersionId');
        $product = $this->getMockBuilder(
            ProductInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->productRepository->expects($this->exactly(1))->method('get')->willReturn($product);
        $this->productStaging->expects($this->exactly(1))->method('schedule');
        $this->assertTrue($this->updateScheduler->schedule('sku', [], 0));
    }
}
