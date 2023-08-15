<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\View\Tab;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Block\Adminhtml\Report\View\Tab\Grid;
use PHPUnit\Framework\TestCase;

class GridTest extends TestCase
{
    /**
     * @var Grid
     */
    protected $reportGridBlock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->reportGridBlock = $this->objectManagerHelper->getObject(
            Grid::class
        );
    }

    public function testCanDisplayContainer()
    {
        $this->assertFalse($this->reportGridBlock->canDisplayContainer());
    }

    public function testGetRowUrl()
    {
        $item = new DataObject();

        $this->assertEquals('', $this->reportGridBlock->getRowUrl($item));
    }
}
