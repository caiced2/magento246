<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Model\ResourceModel\Event\Grid;

use Magento\CatalogEvent\Model\ResourceModel\Event\Grid\State;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for
 */
class StateTest extends TestCase
{
    /**
     * @var State
     */
    protected $state;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->state = new State();
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        foreach ($this->state->toOptionArray() as $item) {
            $this->assertTrue($item instanceof Phrase || is_string($item));
        }
    }
}
