<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Action;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean;
use Magento\TargetRule\Model\ResourceModel\Index;
use Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\TargetRule\Model\RuleFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CleanTest extends TestCase
{
    /**
     * @var Index|MockObject
     */
    private $resourceMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDateMock;

    /**
     * @var Clean
     */
    private $model;

    protected function setUp(): void
    {
        $ruleFactoryMock = $this->createMock(RuleFactory::class);
        $ruleCollectionFactoryMock = $this->createMock(RuleCollectionFactory::class);
        $this->resourceMock = $this->createMock(Index::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->localeDateMock = $this->createMock(TimezoneInterface::class);
        $productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);

        $this->model = new Clean(
            $ruleFactoryMock,
            $ruleCollectionFactoryMock,
            $this->resourceMock,
            $this->storeManagerMock,
            $this->localeDateMock,
            $productCollectionFactoryMock
        );
    }

    /**
     * @dataProvider hoursDataProvider
     * @param string $datetime
     * @param bool $isCleanIndexExecuted
     * @return void
     */
    public function testExecute(string $datetime, bool $isCleanIndexExecuted)
    {
        $websiteMock = $this->createMock(Website::class);
        $storeMock = $this->createMock(Store::class);
        $date = new \DateTime($datetime, new \DateTimeZone('UTC'));

        $websiteMock->expects($this->once())
            ->method('getDefaultStore')
            ->willReturn($storeMock);
        $websiteMock->expects($isCleanIndexExecuted ? $this->once() : $this->never())
            ->method('getStoreIds')
            ->willReturn([1, 2]);
        $this->resourceMock->expects($isCleanIndexExecuted ? $this->once() : $this->never())
            ->method('cleanIndex')
            ->with(null, [1, 2])
            ->willReturnSelf();
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$websiteMock]);
        $this->localeDateMock->expects($this->once())
            ->method('scopeDate')
            ->with($storeMock, null, true)
            ->willReturn($date);

        $this->model->execute();
    }

    public function hoursDataProvider(): array
    {
        return [
            ['1999-08-07 00:05:04', true],
            ['1999-08-07 06:00:00', false],
            ['1999-08-07 12:00:00', false],
        ];
    }
}
