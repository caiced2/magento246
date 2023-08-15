<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Observer\AddSalesRuleFilterObserver;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddSalesRuleFilterObserverTest extends TestCase
{
    /**
     * @var AddSalesRuleFilterObserver
     */
    private $model;

    /**
     * @var Observer|MockObject
     */
    private $eventObserver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->setMethods(['getCollection', 'getRule', 'getForm', 'getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $helper->getObject(
            AddSalesRuleFilterObserver::class
        );
    }

    /**
     * @return void
     */
    public function testAddSalesRuleFilter()
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->setMethods(['addAllowedSalesRulesFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver->expects($this->once())->method('getCollection')->willReturn($collection);
        $collection->expects($this->once())->method('addAllowedSalesRulesFilter');

        $this->model->execute($this->eventObserver);
    }
}
