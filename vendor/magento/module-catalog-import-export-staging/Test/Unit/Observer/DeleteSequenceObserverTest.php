<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogImportExportStaging\Test\Unit\Observer;

use Magento\CatalogImportExportStaging\Observer\DeleteSequenceObserver;
use Magento\CatalogStaging\Model\ResourceModel\ProductSequence\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class DeleteSequenceObserverTest extends TestCase
{
    public function testExecute()
    {
        $objectManager = new ObjectManager($this);
        $productSequenceCollectionMock = $this->createMock(
            Collection::class
        );
        $observerMock = $this->getMockBuilder(Observer::class)
            ->addMethods(['getIdsToDelete'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var DeleteSequenceObserver $model */
        $model = $objectManager->getObject(
            DeleteSequenceObserver::class,
            [
                'productSequenceCollection' => $productSequenceCollectionMock
            ]
        );
        $ids = [1, 2, 3];
        $observerMock->method('getIdsToDelete')
            ->willReturn($ids);
        $productSequenceCollectionMock->expects($this->once())
            ->method('deleteSequence')
            ->with($ids);
        $model->execute($observerMock);
    }
}
