<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Model\Sorting;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VisualMerchandiser\Model\Sorting\SortColor;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SortColorTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var SortColor
     */
    private $model;

    /**
     * Set up instances and mock objects
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            SortColor::class,
            [
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    /**
     * Tests that sort method works without error when sort config option is null.
     */
    public function testSortWhenConfigOptionIsNull()
    {
        $collection = $this->createMock(Collection::class);
        $this->scopeConfig->method('getValue')
            ->with(SortColor::XML_PATH_COLOR_ORDER)
            ->willReturn(null);

        $this->assertEquals($this->model->sort($collection), $collection);
    }
}
