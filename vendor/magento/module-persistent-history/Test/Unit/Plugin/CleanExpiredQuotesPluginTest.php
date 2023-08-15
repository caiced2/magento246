<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PersistentHistory\Plugin\CleanExpiredQuotesPlugin;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Sales\Model\ResourceModel\Collection\ExpiredQuotesCollection;
use PHPUnit\Framework\TestCase;

class CleanExpiredQuotesPluginTest extends TestCase
{
    public function testBeforeExecute()
    {
        $objectManager = new ObjectManager($this);
        $plugin = $objectManager->getObject(CleanExpiredQuotesPlugin::class);

        $subjectMock = $this->getMockBuilder(ExpiredQuotesCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultMock->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->assertSame($resultMock, $plugin->afterGetExpiredQuotes($subjectMock, $resultMock));
    }
}
