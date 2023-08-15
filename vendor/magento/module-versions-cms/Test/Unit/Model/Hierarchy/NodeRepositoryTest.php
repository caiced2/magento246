<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Model\Hierarchy;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterfaceFactory;
use Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterfaceFactory;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\VersionsCms\Model\Hierarchy\NodeRepository;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\VersionsCms\Model\Hierarchy\NodeRepository
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NodeRepositoryTest extends TestCase
{
    /**
     * @var NodeRepository
     */
    protected $repository;

    /**
     * @var MockObject|Node
     */
    protected $nodeResource;

    /**
     * @var MockObject|\Magento\VersionsCms\Model\Hierarchy\Node
     */
    protected $node;

    /**
     * @var MockObject|HierarchyNodeInterface
     */
    protected $nodeData;

    /**
     * @var MockObject|HierarchyNodeSearchResultsInterface
     */
    protected $nodeSearchResult;

    /**
     * @var MockObject|DataObjectHelper
     */
    protected $dataHelper;

    /**
     * @var MockObject|DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var MockObject|Collection
     */
    protected $collection;

    /**
     * @var MockObject
     */
    private $collectionProcessor;

    /**
     * Initialize repository
     */
    protected function setUp(): void
    {
        $this->nodeResource = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectProcessor = $this->getMockBuilder(DataObjectProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeFactory = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $nodeDataFactory = $this->getMockBuilder(HierarchyNodeInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $nodeSearchResultFactory = $this->getMockBuilder(
            HierarchyNodeSearchResultsInterfaceFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $collectionFactory =
            $this->getMockBuilder(CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();

        $this->node = $this->getMockBuilder(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nodeData = $this->getMockBuilder(HierarchyNodeInterface::class)
            ->getMock();
        $this->nodeSearchResult = $this->getMockBuilder(
            HierarchyNodeSearchResultsInterface::class
        )->getMock();
        $this->collection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods([
                'addFieldToFilter',
                'getSize',
                'setCurPage',
                'setPageSize',
                'load',
                'addOrder',
                'addStoreFilter',
                'joinCmsPage',
                'joinMetaData',
                'addCmsPageInStoresColumn',
                'addLastChildSortOrderColumn',
            ])
            ->getMock();

        $nodeFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->node);
        $nodeDataFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->nodeData);
        $nodeSearchResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->nodeSearchResult);
        $collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->collection);
        /**
         * @var NodeFactory $nodeFactory
         * @var HierarchyNodeInterfaceFactory $nodeDataFactory
         * @var HierarchyNodeSearchResultsInterfaceFactory $nodeSearchResultFactory
         * @var CollectionFactory $collectionFactory
         */
        $this->dataHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionProcessor = $this->createMock(
            CollectionProcessorInterface::class
        );

        $this->repository = new NodeRepository(
            $this->nodeResource,
            $nodeFactory,
            $nodeDataFactory,
            $collectionFactory,
            $nodeSearchResultFactory,
            $this->dataHelper,
            $this->dataObjectProcessor,
            $this->collectionProcessor
        );
    }

    /**
     * @test
     */
    public function testSave()
    {
        $this->nodeResource->expects($this->once())
            ->method('save')
            ->with($this->node)
            ->willReturnSelf();
        $this->assertEquals($this->node, $this->repository->save($this->node));
    }

    /**
     * @test
     */
    public function testDeleteById()
    {
        $nodeId = '123';

        $this->node->expects($this->once())
            ->method('getId')
            ->willReturn(true);
        $this->nodeResource->expects($this->once())
            ->method('load')
            ->with($this->node, $nodeId)
            ->willReturn($this->node);
        $this->nodeResource->expects($this->once())
            ->method('delete')
            ->with($this->node)
            ->willReturnSelf();

        $this->assertTrue($this->repository->deleteById($nodeId));
    }

    /**
     * @test
     */
    public function testSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->nodeResource->expects($this->once())
            ->method('save')
            ->with($this->node)
            ->willThrowException(new \Exception());
        $this->repository->save($this->node);
    }

    /**
     * @test
     */
    public function testDeleteException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->nodeResource->expects($this->once())
            ->method('delete')
            ->with($this->node)
            ->willThrowException(new \Exception());
        $this->repository->delete($this->node);
    }

    /**
     * @test
     */
    public function testGetByIdException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $nodeId = '123';

        $this->node->expects($this->once())
            ->method('getId')
            ->willReturn(false);
        $this->nodeResource->expects($this->once())
            ->method('load')
            ->with($this->node, $nodeId)
            ->willReturn($this->node);
        $this->repository->getById($nodeId);
    }

    /**
     * @test
     */
    public function testGetList()
    {
        $total = 10;

        $criteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->getMock();
        /** @var SearchCriteriaInterface $criteria */
        $this->collection->addItem($this->node);
        $this->nodeSearchResult->expects($this->once())->method('setSearchCriteria')->with($criteria)->willReturnSelf();
        $this->collection->expects($this->once())->method('joinCmsPage')->willReturnSelf();
        $this->collection->expects($this->once())->method('joinMetaData')->willReturnSelf();
        $this->collection->expects($this->once())->method('addCmsPageInStoresColumn')->willReturnSelf();
        $this->collection->expects($this->once())->method('addLastChildSortOrderColumn')->willReturnSelf();
        $this->nodeSearchResult->expects($this->once())->method('setTotalCount')->with($total)->willReturnSelf();
        $this->collection->expects($this->once())->method('getSize')->willReturn($total);
        $this->node->expects($this->once())->method('getData')->willReturn(['data']);
        $this->nodeSearchResult->expects($this->once())->method('setItems')->with(['someData'])->willReturnSelf();
        $this->dataHelper->expects($this->once())
            ->method('populateWithArray');
        $this->dataObjectProcessor->expects($this->once())
            ->method('buildOutputDataArray')
            ->willReturn('someData');

        $this->assertEquals($this->nodeSearchResult, $this->repository->getList($criteria));
    }
}
