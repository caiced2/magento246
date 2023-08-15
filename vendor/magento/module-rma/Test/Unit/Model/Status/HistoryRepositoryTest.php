<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Status;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Rma\Model\ResourceModel\Rma\Collection;
use Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Rma\Model\Rma\Status\HistoryFactory;
use Magento\Rma\Model\Rma\Status\HistoryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HistoryRepositoryTest extends TestCase
{
    /**
     * rmaFactory
     *
     * @var MockObject
     */
    private $historyFactoryMock;

    /**
     * Collection Factory
     *
     * @var MockObject
     */
    private $historyCollectionFactorMock;

    /** @var  MockObject */
    private $collectionProcessor;

    /** @var  HistoryRepository */
    private $repository;

    protected function setUp(): void
    {
        $this->historyFactoryMock = $this->getMockBuilder(HistoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyCollectionFactorMock =
            $this->getMockBuilder(CollectionFactory::class)
                ->setMethods(['create'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->collectionProcessor = $this->getMockForAbstractClass(CollectionProcessorInterface::class);

        $this->repository = new HistoryRepository(
            $this->historyFactoryMock,
            $this->historyCollectionFactorMock,
            $this->collectionProcessor
        );
    }

    public function testFind()
    {
        $history1 = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $history2 = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $history1->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $history1->expects($this->once())
            ->method('load')
            ->with(1);
        $history2->expects($this->once())
            ->method('load')
            ->with(2);
        $history2->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(2);
        $items = new \ArrayObject([$history1, $history2]);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $historyCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $historyCollection->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn($items);
        $this->historyCollectionFactorMock->expects($this->once())
            ->method('create')
            ->willReturn($historyCollection);
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $historyCollection);
        $historyCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([1, 2]);
        $this->repository->find($searchCriteria);
    }
}
