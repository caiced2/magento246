<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Rma\Model\ResourceModel\Rma\Collection;
use Magento\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\RmaFactory;
use Magento\Rma\Model\RmaRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RmaRepositoryTest extends TestCase
{
    /**
     * rmaFactory
     *
     * @var MockObject
     */
    private $rmaFactoryMock;

    /**
     * Collection Factory
     *
     * @var MockObject
     */
    private $rmaCollectionFactorMock;

    /** @var  MockObject */
    private $collectionProcessor;

    /** @var  RmaRepository */
    private $repository;

    protected function setUp(): void
    {
        $this->rmaFactoryMock = $this->getMockBuilder(RmaFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rmaCollectionFactorMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionProcessor = $this->getMockForAbstractClass(CollectionProcessorInterface::class);

        $this->repository = new RmaRepository(
            $this->rmaFactoryMock,
            $this->rmaCollectionFactorMock,
            $this->collectionProcessor
        );
    }

    public function testFind()
    {
        $rma1 = $this->getMockBuilder(Rma::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rma2 = $this->getMockBuilder(Rma::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rma1->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $rma1->expects($this->once())
            ->method('load')
            ->with(1);
        $rma2->expects($this->once())
            ->method('load')
            ->with(2);
        $rma2->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(2);
        $items = new \ArrayObject([$rma1, $rma2]);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rmaCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rmaCollection->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn($items);
        $this->rmaCollectionFactorMock->expects($this->once())
            ->method('create')
            ->willReturn($rmaCollection);
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $rmaCollection);
        $rmaCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([1, 2]);
        $this->repository->find($searchCriteria);
    }
}
