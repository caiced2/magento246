<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Backup;
use Magento\Support\Model\Backup\AbstractItem;
use Magento\Support\Model\ResourceModel\Backup\Collection;
use Magento\Support\Observer\UpdateStatusObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateStatusObserverTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var UpdateStatusObserver
     */
    protected $observer;

    /**
     * @var Collection|MockObject
     */
    protected $collectionMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getIterator'])
            ->getMock();

        $this->observer = $this->objectManagerHelper->getObject(
            UpdateStatusObserver::class,
            ['collection' => $this->collectionMock]
        );
    }

    /**
     * @return void
     */
    public function testUpdateStatus()
    {
        /** @var AbstractItem|MockObject $item */
        $item = $this->getMockBuilder(AbstractItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateStatus'])
            ->getMockForAbstractClass();
        $item->expects($this->once())
            ->method('updateStatus');
        $itemCollection = [$item];

        /** @var Backup|MockObject $backup */
        $backup = $this->createMock(Backup::class);
        $backup->expects($this->once())
            ->method('updateStatus');
        $backup->expects($this->once())
            ->method('getItems')
            ->willReturn($itemCollection);
        $backupCollection = [$backup];

        $this->collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('status', ['neq' => Backup::STATUS_COMPLETE])
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($backupCollection));

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertSame($this->observer, $this->observer->execute($observerMock));
    }
}
