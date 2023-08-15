<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Product;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Builder;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\TypeTransitionManager;
use Magento\CatalogStaging\Model\Product\Hydrator;
use Magento\CatalogStaging\Model\Product\Retriever;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\PersisterInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HydratorTest extends TestCase
{
    /** @var Context|MockObject */
    protected $context;

    /**
     * @var Helper|MockObject
     */
    protected $initializationHelper;

    /** @var Builder|MockObject */
    protected $productBuilder;

    /** @var TypeTransitionManager|MockObject */
    protected $productTypeManager;

    /** @var VersionManager|MockObject */
    protected $versionManager;

    /** @var UpdateRepositoryInterface|MockObject */
    protected $updateRepository;

    /** @var CategoryLinkManagementInterface|MockObject */
    protected $categoryLinkManagement;

    /** @var PersisterInterface|MockObject */
    protected $entityPersister;

    /** @var Retriever|MockObject */
    protected $entityRetriever;

    /** @var Product|MockObject */
    protected $product;

    /** @var RequestInterface|MockObject */
    protected $request;

    /** @var UpdateInterface|MockObject */
    protected $update;

    /** @var ManagerInterface|MockObject */
    protected $eventManager;

    /** @var Hydrator */
    protected $hydrator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->initializationHelper = $this->getMockBuilder(
            Helper::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->productBuilder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productTypeManager = $this->getMockBuilder(TypeTransitionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->categoryLinkManagement = $this->getMockBuilder(
            CategoryLinkManagementInterface::class
        )->getMockForAbstractClass();
        $this->entityPersister = $this->getMockBuilder(PersisterInterface::class)
            ->getMockForAbstractClass();
        $this->entityRetriever = $this->getMockBuilder(Retriever::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIdFieldName',
                'setNewsFromDate',
                'setNewsToDate',
                'getId',
                'getSku',
                'getCategoryIds',
                'getEntityId',
                'setStoreId'
            ])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->update = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();

        $this->hydrator = new Hydrator(
            $this->context,
            $this->initializationHelper,
            $this->productBuilder,
            $this->productTypeManager,
            $this->versionManager,
            $this->updateRepository,
            $this->entityRetriever
        );
    }

    public function testHydrate()
    {
        $versionId = 1;
        $startTime = '12/12/2016 14:34:12';
        $endTime = '12/12/2017 14:34:12';
        $storeId = 27;

        $data = [
            'product' => [
                'is_new' => true,
                'copy_to_stores' => [
                    34 => [
                        [
                            'copy_to' => $storeId,
                        ]
                    ],
                ],
                'website_ids' => [
                    34 => true
                ],
            ],
        ];

        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->productBuilder->expects($this->once())
            ->method('build')
            ->with($this->request)
            ->willReturn($this->product);
        $this->initializationHelper->expects($this->once())
            ->method('initialize')
            ->with($this->product)
            ->willReturnArgument(0);
        $this->productTypeManager->expects($this->once())
            ->method('processProduct')
            ->with($this->product);
        $this->versionManager->expects($this->once())
            ->method('getCurrentVersion')
            ->willReturn($this->update);
        $this->update->expects($this->once())
            ->method('getId')
            ->willReturn($versionId);
        $this->updateRepository->expects($this->once())
            ->method('get')
            ->with($versionId)
            ->willReturn($this->update);
        $this->update->expects($this->once())
            ->method('getStartTime')
            ->willReturn($startTime);
        $this->update->expects($this->once())
            ->method('getEndTime')
            ->willReturn($endTime);
        $this->product->expects($this->once())
            ->method('setNewsFromDate')
            ->with($startTime);
        $this->product->expects($this->once())
            ->method('setNewsToDate')
            ->with($endTime);
        $this->product->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->assertEquals($this->product, $this->hydrator->hydrate($data));
    }
}
