<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Model\ResourceModel\Event\Grid;

use Magento\CatalogEvent\Model\ResourceModel\Event\Grid\Statuses;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogEvent\Model\ResourceModel\Event\Grid\Statuses
 */
class StatusesTest extends TestCase
{
    /**
     * @var Statuses
     */
    protected $statuses;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->statuses = new Statuses();
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        foreach ($this->statuses->toOptionArray() as $item) {
            $this->assertTrue($item instanceof Phrase || is_string($item));
        }
    }
}
