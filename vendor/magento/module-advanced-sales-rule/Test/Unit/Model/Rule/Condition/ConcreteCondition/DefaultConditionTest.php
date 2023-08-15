<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition\ConcreteCondition;

use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class DefaultConditionTest extends TestCase
{
    /**
     * @var DefaultCondition
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->model = $this->objectManager->getObject(
            DefaultCondition::class,
            []
        );
    }

    /**
     * test IsFilterable
     */
    public function testIsFilterable()
    {
        $this->assertFalse($this->model->isFilterable());
    }

    /**
     * test GetFilterGroups
     */
    public function testGetFilterGroups()
    {
        $this->assertIsArray($this->model->getFilterGroups());
        $this->assertEmpty($this->model->getFilterGroups());
    }
}
