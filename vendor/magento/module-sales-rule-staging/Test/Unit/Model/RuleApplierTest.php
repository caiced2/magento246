<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesRuleStaging\Model\RuleApplier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RuleApplierTest extends TestCase
{
    /**
     * @var RuleApplier
     */
    protected $model;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManagerMock;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $className = RuleApplier::class;

        $this->eventManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);

        $this->model = $objectManager->getObject(
            $className,
            [
                'eventManager' => $this->eventManagerMock,
            ]
        );
    }

    public function testExecuteEmpty()
    {
        $ids = [];
        $this->eventManagerMock->expects($this->never())
            ->method('dispatch');
        $this->model->execute($ids);
    }

    public function testExecute()
    {
        $ids = [1, 2];
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('sales_rule_updated', ['entity_ids' => $ids]);
        $this->model->execute($ids);
    }
}
