<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeSearchResultsInterface;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\CurrentNodeResolver;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CurrentNodeResolverTest extends TestCase
{
    /**
     * @var NodeFactory|MockObject
     */
    private $hierarchyNodeFactoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var HierarchyNodeRepositoryInterface|MockObject
     */
    private $hierarchyNodeRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var CurrentNodeResolver
     */
    private $model;

    protected function setUp(): void
    {
        $this->hierarchyNodeFactoryMock = $this->getMockBuilder(NodeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->hierarchyNodeRepositoryMock = $this->getMockBuilder(HierarchyNodeRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CurrentNodeResolver(
            $this->hierarchyNodeFactoryMock,
            $this->storeManagerMock,
            $this->hierarchyNodeRepositoryMock,
            $this->searchCriteriaBuilderMock
        );
    }

    public function testGet()
    {
        $pageId = 1;
        $storeId = 1;
        $nodeId = 1;

        $requestUrl = 'test';

        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('page_id', false)
            ->willReturn($pageId);

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $nodeData = [
            'data' => [
                'scope' => Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getScope')
            ->willReturn(Node::NODE_SCOPE_STORE);
        $nodeMock->expects($this->once())
            ->method('getScopeId')
            ->willReturn($storeId);
        $nodeMock->expects($this->once())
            ->method('loadByRequestUrl')
            ->with($requestUrl)
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getId')
            ->willReturn($nodeId);

        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->with($nodeData)
            ->willReturn($nodeMock);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock->expects($this->exactly(3))
            ->method('addFilter')
            ->willReturnMap([
                [HierarchyNodeInterface::PAGE_ID, $pageId, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE, Node::NODE_SCOPE_STORE, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE_ID, $storeId, 'eq', $this->searchCriteriaBuilderMock],
            ]);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $nodes = [
            [
                'page_id' => $pageId,
                'request_url' => $requestUrl,
            ]
        ];

        $hierarchyNodeSearchResultsMock = $this->getMockBuilder(HierarchyNodeSearchResultsInterface::class)
            ->getMockForAbstractClass();
        $hierarchyNodeSearchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn($nodes);

        $this->hierarchyNodeRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($hierarchyNodeSearchResultsMock);

        $this->assertEquals($nodeMock, $this->model->get($requestMock));
    }

    public function testGetNull()
    {
        $pageId = 1;
        $storeId = 1;

        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('page_id', false)
            ->willReturn($pageId);

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $nodeData = [
            'data' => [
                'scope' => Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getScope')
            ->willReturn(Node::NODE_SCOPE_STORE);
        $nodeMock->expects($this->once())
            ->method('getScopeId')
            ->willReturn($storeId);

        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->with($nodeData)
            ->willReturn($nodeMock);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock->expects($this->exactly(3))
            ->method('addFilter')
            ->willReturnMap([
                [HierarchyNodeInterface::PAGE_ID, $pageId, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE, Node::NODE_SCOPE_STORE, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE_ID, $storeId, 'eq', $this->searchCriteriaBuilderMock],
            ]);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $hierarchyNodeSearchResultsMock = $this->getMockBuilder(HierarchyNodeSearchResultsInterface::class)
            ->getMockForAbstractClass();
        $hierarchyNodeSearchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn(null);

        $this->hierarchyNodeRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($hierarchyNodeSearchResultsMock);

        $this->assertNull($this->model->get($requestMock));
    }

    public function testGetHttpRequest()
    {
        $pageId = 1;
        $storeId = 1;
        $nodeId = 1;
        $pathInfo = '/test1/test2';

        $requestUrl = 'test1/test2';

        $requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getPathInfo']
        );
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('page_id', false)
            ->willReturn($pageId);
        $requestMock->expects($this->once())->method('getPathInfo')->willReturn($pathInfo);

        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $nodeData = [
            'data' => [
                'scope' => Node::NODE_SCOPE_STORE,
                'scope_id' => $storeId,
            ],
        ];

        $nodeMock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nodeMock->expects($this->once())
            ->method('getHeritage')
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getScope')
            ->willReturn(Node::NODE_SCOPE_STORE);
        $nodeMock->expects($this->once())
            ->method('getScopeId')
            ->willReturn($storeId);
        $nodeMock->expects($this->once())
            ->method('loadByRequestUrl')
            ->with($requestUrl)
            ->willReturnSelf();
        $nodeMock->expects($this->once())
            ->method('getId')
            ->willReturn($nodeId);

        $this->hierarchyNodeFactoryMock->expects($this->once())
            ->method('create')
            ->with($nodeData)
            ->willReturn($nodeMock);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock->expects($this->exactly(3))
            ->method('addFilter')
            ->willReturnMap([
                [HierarchyNodeInterface::PAGE_ID, $pageId, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE, Node::NODE_SCOPE_STORE, 'eq', $this->searchCriteriaBuilderMock],
                [HierarchyNodeInterface::SCOPE_ID, $storeId, 'eq', $this->searchCriteriaBuilderMock],
            ]);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $nodes = [
            [
                'page_id' => $pageId,
                'request_url' => $requestUrl,
            ]
        ];

        $hierarchyNodeSearchResultsMock = $this->getMockBuilder(HierarchyNodeSearchResultsInterface::class)
            ->getMockForAbstractClass();
        $hierarchyNodeSearchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn($nodes);

        $this->hierarchyNodeRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($hierarchyNodeSearchResultsMock);

        $this->assertEquals($nodeMock, $this->model->get($requestMock));
    }
}
