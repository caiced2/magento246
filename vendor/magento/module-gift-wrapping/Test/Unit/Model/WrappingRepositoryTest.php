<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftWrapping\Api\Data\WrappingSearchResultsInterface;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection;
use Magento\GiftWrapping\Model\WrappingRepository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WrappingRepositoryTest extends TestCase
{
    /** @var WrappingRepository */
    protected $wrappingRepository;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var MockObject */
    protected $wrappingFactoryMock;

    /** @var MockObject */
    protected $collectionFactoryMock;

    /** @var MockObject */
    protected $searchResultFactoryMock;

    /** @var MockObject */
    protected $searchResultsMock;

    /** @var MockObject */
    protected $resourceMock;

    /** @var MockObject */
    protected $storeManagerMock;

    /** @var MockObject */
    protected $wrappingCollectionMock;

    /** @var MockObject */
    protected $wrappingMock;

    /** @var MockObject */
    protected $storeMock;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessor;

    protected function setUp(): void
    {
        $this->wrappingFactoryMock = $this->createPartialMock(
            \Magento\GiftWrapping\Model\WrappingFactory::class,
            ['create']
        );
        $this->collectionFactoryMock = $this->createPartialMock(
            \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory::class,
            ['create']
        );

        $this->wrappingCollectionMock =
            $this->createMock(Collection::class);
        $methods = ['create'];
        $this->searchResultFactoryMock = $this->createPartialMock(
            \Magento\GiftWrapping\Api\Data\WrappingSearchResultsInterfaceFactory::class,
            $methods
        );
        $this->searchResultsMock = $this->createMock(
            WrappingSearchResultsInterface::class
        );
        $this->resourceMock =
            $this->createMock(Wrapping::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->wrappingMock = $this->createMock(\Magento\GiftWrapping\Model\Wrapping::class);
        $this->storeMock =
            $this->createPartialMock(Store::class, ['getBaseCurrencyCode']);
        $this->collectionProcessor = $this->createMock(
            CollectionProcessorInterface::class
        );
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->wrappingRepository = new WrappingRepository(
            $this->wrappingFactoryMock,
            $this->collectionFactoryMock,
            $this->searchResultFactoryMock,
            $this->resourceMock,
            $this->storeManagerMock,
            $this->collectionProcessor
        );
    }

    public function testGetException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        list($id, $storeId) = [1, 1];
        /** @var MockObject $wrappingMock */
        $wrappingMock = $this->createMock(\Magento\GiftWrapping\Model\Wrapping::class);

        $this->wrappingFactoryMock->expects($this->once())->method('create')->willReturn($wrappingMock);
        $wrappingMock->expects($this->once())->method('setStoreId')->with($storeId);
        $this->resourceMock->expects($this->once())->method('load')->with($wrappingMock, $id);
        $wrappingMock->expects($this->once())->method('getId')->willReturn(null);

        $this->wrappingRepository->get($id, $storeId);
    }

    public function testGetSuccess()
    {
        list($id, $storeId) = [1, 1];
        /** @var MockObject $wrappingMock */
        $wrappingMock = $this->createMock(\Magento\GiftWrapping\Model\Wrapping::class);

        $this->wrappingFactoryMock->expects($this->once())->method('create')->willReturn($wrappingMock);
        $wrappingMock->expects($this->once())->method('setStoreId')->with($storeId);
        $this->resourceMock->expects($this->once())->method('load')->with($wrappingMock, $id);
        $wrappingMock->expects($this->once())->method('getId')->willReturn($id);

        $this->assertSame($wrappingMock, $this->wrappingRepository->get($id, $storeId));
    }

    public function testDelete()
    {
        $this->resourceMock->expects($this->once())->method('delete')->with($this->wrappingMock);
        $this->wrappingRepository->delete($this->wrappingMock);
    }

    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('"1" gift wrapping couldn\'t be removed.');
        $this->wrappingMock->expects($this->once())->method('getWrappingId')->willReturn(1);
        $this->resourceMock
            ->expects($this->once())
            ->method('delete')
            ->with($this->wrappingMock)
            ->willThrowException(new \Exception());
        $this->wrappingRepository->delete($this->wrappingMock);
    }

    public function testDeleteById()
    {
        $id = 1;
        $this->wrappingFactoryMock->expects($this->once())->method('create')->willReturn($this->wrappingMock);
        $this->resourceMock->expects($this->once())->method('load')->with($this->wrappingMock, $id);
        $this->resourceMock->expects($this->once())->method('delete')->with($this->wrappingMock);
        $this->wrappingMock->expects($this->once())->method('getId')->willReturn($id);
        $this->assertTrue($this->wrappingRepository->deleteById($id));
    }

    public function testSave()
    {
        $imageContent = base64_encode('image content');
        $imageName = 'image.jpg';
        $this->wrappingMock
            ->expects($this->once())
            ->method('getImageBase64Content')
            ->willReturn($imageContent);
        $this->wrappingMock->expects($this->once())->method('getImageName')->willReturn($imageName);

        $this->wrappingMock->expects($this->once())->method('getWrappingId')->willReturn(null);
        $this->wrappingMock->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->wrappingMock->expects($this->once())->method('setStoreId')->with(Store::DEFAULT_STORE_ID);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->resourceMock->expects($this->once())->method('save')->with($this->wrappingMock);
        $this->wrappingRepository->save($this->wrappingMock);
    }

    public function testUpdate()
    {
        $id = 1;
        $imageContent = base64_encode('image content');
        $imageName = 'image.jpg';
        $this->wrappingFactoryMock->expects($this->once())->method('create')->willReturn($this->wrappingMock);
        $this->resourceMock
            ->expects($this->once())
            ->method('load')
            ->with($this->wrappingMock, $id)
            ->willReturn($this->wrappingMock);
        $this->wrappingMock->expects($this->once())->method('getData')->willReturn(['data']);
        $this->wrappingMock->expects($this->once())->method('addData')->with(['data'])->willReturnSelf();
        $this->wrappingMock->expects($this->once())->method('getId')->willReturn($id);
        $this->wrappingMock
            ->expects($this->once())
            ->method('getImageBase64Content')
            ->willReturn($imageContent);
        $this->wrappingMock->expects($this->once())->method('getImageName')->willReturn($imageName);

        $this->wrappingMock->expects($this->once())->method('getWrappingId')->willReturn($id);
        $this->wrappingMock->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->resourceMock->expects($this->once())->method('save')->with($this->wrappingMock);

        $this->wrappingRepository->save($this->wrappingMock);
    }

    public function testSaveWithInvalidCurrencyCode()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->expectExceptionMessage(
            'A valid currency code wasn\'t entered. Enter a valid UA currency code and try again.'
        );
        $id = 1;
        $this->resourceMock->expects($this->never())->method('load');
        $this->wrappingMock
            ->expects($this->never())
            ->method('getImageBase64Content');
        $this->wrappingMock->expects($this->once())->method('getWrappingId')->willReturn($id);
        $this->wrappingMock->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getBaseCurrencyCode')->willReturn('UA');
        $this->wrappingRepository->save($this->wrappingMock);
    }

    public function testGetListStatusFilter()
    {
        $criteriaMock = $this->preparedCriteriaFilterMock('status');
        list($collectionMock) = $this->getPreparedCollectionAndItems();
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($criteriaMock, $collectionMock);

        $this->searchResultsMock->expects($this->once())->method('setItems')->willReturnSelf();
        $this->searchResultsMock->expects($this->once())->method('setTotalCount')->willReturnSelf();
        $this->searchResultsMock->expects($this->once())->method('setSearchCriteria')->willReturnSelf();
        $this->searchResultFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->searchResultsMock);
        $this->wrappingRepository->getList($criteriaMock);
    }

    public function testFindStoreIdFilter()
    {
        $criteriaMock = $this->preparedCriteriaFilterMock('store_id');
        list($collectionMock) = $this->getPreparedCollectionAndItems();
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($criteriaMock, $collectionMock);

        $this->searchResultsMock->expects($this->once())->method('setItems')->willReturnSelf();
        $this->searchResultsMock->expects($this->once())->method('setTotalCount')->willReturnSelf();
        $this->searchResultsMock->expects($this->once())->method('setSearchCriteria')->willReturnSelf();
        $this->searchResultFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->searchResultsMock);
        $this->wrappingRepository->getList($criteriaMock);
    }

    /**
     * @param string|null $condition
     * @param string $expectedCondition
     * @dataProvider conditionDataProvider
     */
    public function testFindByCondition($condition)
    {
        $field = 'condition';
        $criteriaMock = $this->preparedCriteriaFilterMock($field, $condition);
        list($collectionMock) = $this->getPreparedCollectionAndItems();
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($criteriaMock, $collectionMock);
        $this->searchResultsMock->expects($this->once())->method('setItems')->willReturnSelf();
        $this->searchResultsMock->expects($this->once())->method('setTotalCount')->willReturnSelf();
        $this->searchResultsMock->expects($this->once())->method('setSearchCriteria')->willReturnSelf();
        $this->searchResultFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->searchResultsMock);
        $this->wrappingRepository->getList($criteriaMock);
    }

    /**
     * @return array
     */
    public function conditionDataProvider()
    {
        return [
            [null],
            ['not_eq']
        ];
    }

    /**
     * Prepares mocks
     *
     * @param $filterType
     * @param string $condition
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return SearchCriteria|MockObject
     */
    private function preparedCriteriaFilterMock($filterType, $condition = 'eq')
    {
        /** @var MockObject $criteriaMock */
        $criteriaMock = $this->createMock(SearchCriteria::class);
        return $criteriaMock;
    }

    /**
     * Prepares collection
     * @return array
     */
    private function getPreparedCollectionAndItems()
    {
        $items = [new DataObject()];
        $collectionMock =
            $this->createMock(Collection::class);

        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn(
            $collectionMock
        );
        $collectionMock->expects($this->once())->method('addWebsitesToResult');
        $collectionMock->expects($this->once())->method('getItems')->willReturn($items);

        return [$collectionMock, $items];
    }
}
